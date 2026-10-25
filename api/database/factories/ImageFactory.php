<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . '_' . time(),
            'path' => 'images/' . $this->faker->word() . '/' . $this->faker->word() . '.' . $this->faker->fileExtension(),
            'type' => $this->faker->randomElement(['image', 'document', 'video']),
            'size' => (string)$this->faker->numberBetween(1000, 10000000),
            'extension' => $this->faker->fileExtension(),
            'mime_type' => $this->faker->mimeType(),
            'original_name' => $this->faker->word() . '.' . $this->faker->fileExtension(),
            'company_id' => null,
            'language' => $this->faker->randomElement(['en', 'es', 'pt', null]),
            'disk' => Image::defaultDisk(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Image $image) {
            $faker = fake();
            $faker->addProvider(new FakerPicsumImagesProvider($faker));

            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $filePath = $faker->image($tempDir, 800, 600);
            $fileName = basename($filePath);

            $fakeFile = new UploadedFile(
                $filePath,
                $fileName,
                mime_content_type($filePath),
                null,
                true
            );

            //$image = new Image();
            $image->name = $fakeFile->getClientOriginalExtension();
            //$image->company_id = $companyId;
            //$image->name = $imageName;
            $image->disk = Image::defaultDisk();

            $image->type = $fakeFile->getClientMimeType();
            $image->mime_type = $fakeFile->getClientMimeType();
            $image->extension = $fakeFile->getClientOriginalExtension();
            $image->original_name = $fakeFile->getClientOriginalName();

            $content = $fakeFile->getContent();

            $image = $image->putContent($content);
            $image->save();

            @unlink($filePath);
        });
    }
}
