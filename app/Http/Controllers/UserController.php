<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function uploadProfilePicture(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB
        ]);

        $user = Auth::user();

        if ($user->profile_picture_url) {
            $this->deleteFromCloudinary($user->profile_picture_url);
        }

        $uploaded = Cloudinary::uploadApi()->upload(
            $request->file('image')->getRealPath(),
            [
                'folder' => 'profile_pictures',
                'public_id' => 'user_' . $user->id,
                'overwrite' => true,
            ]
        );

        $user->profile_picture_url = $uploaded['secure_url'];
        $user->save();

        return response()->json([
            'message' => 'Profile picture uploaded',
            'profile_picture_url' => $user->profile_picture_url,
        ]);
    }

    public function deleteProfilePicture()
    {
        $user = Auth::user();

        if (!$user->profile_picture_url) {
            return response()->json(['message' => 'No image found'], 404);
        }

        $this->deleteFromCloudinary($user->profile_picture_url);

        $user->profile_picture_url = null;
        $user->save();

        return response()->json(['message' => 'Profile picture deleted']);
    }

    private function deleteFromCloudinary($url)
    {
        $publicId = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        Cloudinary::uploadApi()->destroy('profile_pictures/' . $publicId);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json($user);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
