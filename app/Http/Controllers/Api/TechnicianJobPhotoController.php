<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTechnicianJobPhotoRequest;
use App\Http\Resources\Api\JobPhotoResource;
use App\Models\JobPhoto;
use App\Models\TechnicianJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class TechnicianJobPhotoController extends Controller
{
    public function store(StoreTechnicianJobPhotoRequest $request, TechnicianJob $technicianJob): JsonResponse
    {
        $data = $request->validated();
        $photo = $request->file('photo');

        abort_unless($photo instanceof UploadedFile, 422, 'Photo upload is required.');

        $path = $photo->store("technician-jobs/{$technicianJob->getKey()}", 'public');

        $jobPhoto = $technicianJob->photos()->create([
            'photo_path' => $path,
            'photo_type' => $data['photo_type'] ?? JobPhoto::TYPE_AFTER,
            'uploaded_at' => now(),
        ]);

        return (new JobPhotoResource($jobPhoto))
            ->response()
            ->setStatusCode(201);
    }
}
