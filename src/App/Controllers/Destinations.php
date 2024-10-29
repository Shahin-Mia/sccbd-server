<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Mailer;
use App\Repository\DestinationRepository;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;

class Destinations
{
    public function __construct(private Validator $validator, private DestinationRepository $repository, private Mailer $mailer)
    {
        $this->validator->mapFieldsRules(
            [
                "destination_name" => ["required"],
                "description" => ["required"],
                "published" => ["required"],
                "created_by" => ["required", ["integer"]]
            ]
        );
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $this->validator = $this->validator->withData($data);

        if (! $this->validator->validate()) {
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withStatus(422);
        }

        $files = $request->getUploadedFiles();

        if (!array_key_exists("destination_thumbnail", $files) || !array_key_exists("destination_images", $files)) {
            $response->getBody()->write(json_encode("images is missing from data!"));
            return $response->withStatus(422);
        }
        $destination_thumbnail = $files["destination_thumbnail"];
        $destination_images = $files["destination_images"];

        try {
            $thumbnail_path = $this->moveToFolder($destination_thumbnail);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "status" => "error",
                "message" => "There is a problem with destination thumbnail!",
                "error" => $e->getMessage()
            ]));
            return $response->withStatus(422);
        }

        $uploaded_destination_images = [];

        foreach ($destination_images as $file) {
            try {
                $uploaded_destination_images[] = $this->moveToFolder($file);
            } catch (Exception $e) {
                foreach ($uploaded_destination_images as $image) {
                    if (file_exists(IMAGE_FOLDER . $file)) {
                        unlink(IMAGE_FOLDER . $file);
                    }
                }
                $response->getBody()->write(json_encode(["status" => "error", "message" => "There is a problem with destination images!", "error" => $e->getMessage()]));
                return $response->withStatus(422);
            }
        }

        $data["destination_thumbnail"] = $thumbnail_path;
        $data["destination_images"] = implode(",", $uploaded_destination_images);


        try {
            $this->repository->create($data);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["message" => $e->getMessage()]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "Destination was created successfully!"
        ]));

        return $response;
    }


    public function getAll(Request $request, Response $response)
    {
        $data = $this->repository->getAll();

        $body = json_encode($data, JSON_NUMERIC_CHECK);

        $response->getBody()->write($body);

        return $response;
    }
    public function delete(Request $request, Response $response, $id)
    {
        $destination = $this->repository->getById((int)$id);

        if (!$destination) {
            $response->getBody()->write(json_encode([
                "status" => "error",
                "message" => "Destination not found!"
            ]));
            return $response;
        }
        $row = $this->repository->delete((int) $id);
        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "Destination was deleted!",
            "rows" => $row
        ]));

        return $response;
    }
    public function getById(Request $request, Response $response, $id)
    {
        $destination = $this->repository->getById((int)$id);

        if (!$destination) {
            $response->getBody()->write(json_encode([
                "status" => "error",
                "message" => "Destination not found!"
            ]));
            return $response;
        }

        $response->getBody()->write(json_encode([
            "status" => "success",
            "destination" => $destination
        ]));

        return $response;
    }

    public function update(Request $request, Response $response, $id)
    {
        $data = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $destination = $this->repository->getById((int)$id);
        if (!$destination) {
            $response->getBody()->write(json_encode([
                "status" => "error",
                "message" => "Destination not found!"
            ]));
            return $response;
        }

        if (array_key_exists("destination_images", $files) || array_key_exists("destination_thumbnail", $files)) {
            if (isset($files["destination_images"])) {
                $uploaded_destination_images = [];
                foreach ($files["destination_images"] as $file) {
                    try {
                        $uploaded_destination_images[] = $this->moveToFolder($file);
                    } catch (Exception $e) {
                        foreach ($uploaded_destination_images as $image) {
                            if (file_exists(IMAGE_FOLDER . $file)) {
                                unlink(IMAGE_FOLDER . $file);
                            }
                        }
                        $response->getBody()->write(json_encode([
                            "status" => "error",
                            "message" => "There is a problem with destination images!",
                            "error" => $e->getMessage()
                        ]));
                        return $response->withStatus(422);
                    }
                }
                $previousUploads = explode(",", $destination["destination_images"]);
                foreach ($previousUploads as $filename) {
                    try {
                        unlink(IMAGE_FOLDER . $filename);
                    } catch (Exception $e) {
                        $response->getBody()->write(json_encode([
                            "status" => "error",
                            "message" => "There is a problem with destination images!",
                            "error" => $e->getMessage()
                        ]));
                        return $response->withStatus(422);
                    }
                }
                $data["destination_images"] = implode(",", $uploaded_destination_images);
            }

            if (isset($files["destination_thumbnail"])) {
                try {
                    $data["destination_thumbnail"] = $this->moveToFolder($files["destination_thumbnail"]);
                    unlink(IMAGE_FOLDER . $destination["destination_thumbnail"]);
                } catch (Exception $e) {
                    $response->getBody()->write(json_encode([
                        "status" => "error",
                        "message" => "There is a problem with destination thumbnail!",
                        "error" => $e->getMessage()
                    ]));
                    return $response->withStatus(422);
                }
            }
        }

        try {
            $this->repository->update($destination, $data);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "status" => "error",
                "message" => "There is a problem with updating data!",
                "error" => $e->getMessage()
            ]));
            return $response->withStatus(422);
        }

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "Destination has been updated!"
        ]));

        return $response;
    }


    public function moveToFolder($file)
    {
        $error = $file->getError();

        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception("file not properly uploaded!");
        }

        if ($file->getSize() > 2097152) {
            throw new Exception("Uploaded file is larger than 2MB!");
        }

        $mediaType = ["image/png", "image/jpg", "image/webp", "image/jpeg"];

        if (!in_array($file->getClientMediaType(), $mediaType)) {
            throw new Exception("File format is not supported!");
        }

        $fileNameParts = pathinfo($file->getClientFilename());
        $filename = preg_replace("/\W/", "_", $fileNameParts["filename"]);
        $extention = $fileNameParts["extension"];

        $filename = $filename . time() . "." . $extention;

        try {
            $file->moveTo(IMAGE_FOLDER . $filename);
        } catch (Exception $e) {
            throw new Exception("There is a problem in moving files to folder");
        }

        return $filename;
    }
}
