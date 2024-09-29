<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Mailer;
use App\Repository\UserRepository;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;

class Users
{
    public function __construct(private Validator $validator, private UserRepository $repository, private Mailer $mailer)
    {
        $this->validator->mapFieldsRules(
            [
                "username" => ["required"],
                "email" => ["required", "email"],
                "role" => ["required", ["in", ["admin", "maintainer", "viewer", "student"]]],
                "password" => ["required"]
            ]
        );

        $this->validator->rule(function ($field, $value, $params, $fields) {
            return $this->repository->find("email", $value) === false;
        }, "email")->message("{field} already in use!");
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (array_key_exists("phone", $data) && !array_key_exists("role", $data)) {
            return $this->createStudent($request, $response);
        }

        $this->validator = $this->validator->withData($data);

        if (! $this->validator->validate()) {
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withStatus(422);
        }

        $files = $request->getUploadedFiles();

        if (!array_key_exists("profile_image", $files)) {
            $response->getBody()->write(json_encode("image is missing from data!"));
            return $response->withStatus(422);
        }
        $file = $files["profile_image"];

        $error = $file->getError();

        if ($error !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode("file not properly uploaded!"));
            return $response->withStatus(422);
        }

        if ($file->getSize() > 2097152) {
            $response->getBody()->write(json_encode("Uploaded file is larger than 2MB!"));
            return $response->withStatus(422);
        }

        $mediaType = ["image/png", "image/jpg", "image/webp", "image/jpeg"];

        if (!in_array($file->getClientMediaType(), $mediaType)) {
            $response->getBody()->write(json_encode("File format is not supported!"));
            return $response->withStatus(422);
        }

        $fileNameParts = pathinfo($file->getClientFilename());
        $filename = preg_replace("/\W/", "_", $fileNameParts["filename"]);
        $extention = $fileNameParts["extension"];

        $filename = $filename . time() . "." . $extention;

        $data["profile_image"] = $filename;
        $data["password_hash"] = password_hash($data["password"], PASSWORD_DEFAULT);
        $api_key = bin2hex(random_bytes(16));
        $encryption_key = Key::loadFromAsciiSafeString($_ENV["ENCRYPTION_KEY"]);
        $data["api_key"] = Crypto::encrypt($api_key, $encryption_key);
        $data["api_key_hash"] = hash_hmac("sha256", $api_key, $_ENV["SECRET_KEY"]);

        try {
            $this->repository->create($data);
            $file->moveTo(APP_ROOT . "/../sccbd/public/images/" . $filename);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["message" => $e->getMessage()]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "User was created successfully!",
            "api_key" => $api_key
        ]));

        return $response;
    }


    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $user = $this->repository->find("email", $data["email"]);

        if ($user && password_verify($data["password"], $user["password"])) {

            $encryption_key = Key::loadFromAsciiSafeString($_ENV["ENCRYPTION_KEY"]);

            $api_key = Crypto::decrypt($user["api_key"], $encryption_key);

            $userData = [];

            $userData["username"] = $user["username"];
            $userData["profile_image"] = $user["profile_image"];
            $userData["email"] = $user["email"];
            $userData["role"] = $user["role"];
            $userData["api_key"] = $api_key;

            $response->getBody()->write(json_encode([
                "user" => $userData,
                "message" => "login successfully!"
            ]));

            return $response;
        }

        $response->getBody()->write(json_encode([
            "status" => "error",
            "message" => "Email or password is incorrect!"
        ]));

        return $response;
    }

    public function createStudent(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (!array_key_exists("role", $data)) {
            $data["role"] = "student";
        }

        $this->validator = $this->validator->withData($data);

        if (! $this->validator->validate()) {
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withStatus(422);
        }


        $data["password_hash"] = password_hash($data["password"], PASSWORD_DEFAULT);
        $api_key = bin2hex(random_bytes(16));
        $encryption_key = Key::loadFromAsciiSafeString($_ENV["ENCRYPTION_KEY"]);
        $data["api_key"] = Crypto::encrypt($api_key, $encryption_key);
        $data["api_key_hash"] = hash_hmac("sha256", $api_key, $_ENV["SECRET_KEY"]);

        $email_activation_token = bin2hex(random_bytes(16));

        $data["email_activation_token"] = hash_hmac("sha256", $email_activation_token, $_ENV["SECRET_KEY"]);

        $this->repository->createStudent($data);

        $mail = $this->mailer->getMailer();

        $mail->setFrom("noreply@sccbd.net", "SCCBD");
        $mail->addAddress($data["email"]);

        $mail->Subject = "Account Activation";

        $mail->Body = <<<END

            Click <a href="$_ENV[CLIENT_SERVER]/account-activation?token=$email_activation_token">here</a> to Activate your account
        
            END;

        try {
            $mail->send();
        } catch (Exception $e) {
            $response->getBody()->write(json_encode($e));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "You have signed up successfully! An activation email was sent to your email address",
            "api_key" => $api_key
        ]));

        return $response;
    }

    public function sendMailToUser(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $user = $this->repository->find("email", $data["email"]);

        if ($user) {

            $encryption_key = Key::loadFromAsciiSafeString($_ENV["ENCRYPTION_KEY"]);
            $token = Crypto::decrypt($user["api_key"], $encryption_key);

            $mail = $this->mailer->getMailer();

            $mail->setFrom("noreply@sccbd.net", "SCCBD");
            $mail->addAddress($data["email"]);

            $mail->Subject = "Password Reset";

            $mail->Body = <<<END

            Click <a href="$_ENV[CLIENT_SERVER]/reset-password?token=$token">here</a> to reset your password
        
            END;

            try {
                $mail->send();
            } catch (Exception $e) {
                $response->getBody()->write(json_encode($e));
                return $response->withStatus(500);
            }

            $response->getBody()->write(json_encode([
                "status" => "success",
                "message" => "A reset email has been sent to your email address"
            ]));

            return $response;
        }

        $response->getBody()->write(json_encode([
            "status" => "error",
            "message" => "There is a problem occurs!"
        ]));

        return $response->withStatus(500);
    }

    public function resetPassword(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        if (!array_key_exists("token", $data)) {
            return $response->withStatus(404);
        }

        $token = hash_hmac("sha256", $data["token"], $_ENV["SECRET_KEY"]);

        $user = $this->repository->find("api_key_hash", $token);

        if ($user) {

            $password = $data["password_hash"] = password_hash($data["password"], PASSWORD_DEFAULT);
            $this->repository->updatePassword((int) $user["id"], $password);

            $response->getBody()->write(json_encode([
                "status" => "success",
                "message" => "User updated successfully!"
            ]));

            return $response;
        }

        $response->getBody()->write(json_encode([
            "status" => "error",
            "message" => "Internal server error!"
        ]));

        return $response;
    }


    public function activateAccount(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        if (!array_key_exists("email_activation_token", $data)) {
            return $response->withStatus(404);
        }

        $email_activation_token = hash_hmac("sha256", $data["email_activation_token"], $_ENV["SECRET_KEY"]);

        $user = $this->repository->find("email_activation_token", $email_activation_token);

        if ($user) {
            $this->repository->updateActivationToken((int) $user["id"]);

            $response->getBody()->write(json_encode([
                "status" => "success",
                "message" => "Account has activated successfully!"
            ]));
        }
    }
}
