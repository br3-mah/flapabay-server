<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        // Placeholder for the index method logic
    }

    public function test()
    {
        try {
            // Query to fetch all users from the wp_users table
            $users = DB::table('wp_users')->select(
                'ID',
                'user_login',
                'user_email',
                'user_registered',
                'display_name'
            )->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($user_id){
        try {
            $id = $user_id;
            // Find the user by ID from the wp_users table
            $user = User::with('details')->where('id', $id)->first();

            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user details and return the updated user data.
     */
    public function update(Request $request, $user_id)
    {

        try {
            // Validate request data
            $validatedData = Validator::make($request->all(), [
                // These are in User Model
                'email' => 'nullable|email',
                'fname' => 'nullable|string|max:255',
                'lname' => 'nullable|string|max:255',

                // These are in UserDetail Model
                'bio' => 'nullable|string',
                'live_in' => 'nullable|string',
                'paypal_email' => 'nullable|email',
                'phone' => 'nullable|string|max:15',
                'website' => 'nullable|url',
                'skype' => 'nullable|url',
                'facebook' => 'nullable|url',
                'twitter' => 'nullable|url',
                'linkedin' => 'nullable|url',
                'pinterest' => 'nullable|url',
                'youtube' => 'nullable|url',
            ]);

            if ($validatedData->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validatedData->errors()
                ], 422);
            }

            // Extract validated data
            $data = $validatedData->validated();

            // Find the user by ID
            $user = User::with('details')->findOrFail($user_id);

            // Update User model
            $user->update($data);

            // Update UserDetail model if it exists
            $userDetail = UserDetail::where('user_id', $user_id)->first();
            if ($userDetail) {
                $userDetail->update($data);
            } else {
                // Optionally, create a new UserDetail if it doesn't exist
                $userDetail = UserDetail::create(array_merge($data, ['user_id' => $user_id]));
            }

            // Return the updated user data
            return response()->json([
                'success' => true,
                'message' => 'User  updated successfully',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfilePicture(Request $request, $user_id)
    {
        try {
            // Validate the incoming request
            $validatedData = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
            ]);

            // Check if validation fails
            if ($validatedData->fails()) {
                return response()->json(['error' => $validatedData->errors()], 400);
            }

            // Retrieve the uploaded file
            $file = $request->file('profile_picture');
            $fileName = time() . '_' . $file->getClientOriginalName();

            // Define the local path where the image will be stored temporarily
            $localPath = storage_path('app/temp/' . $fileName);

            // Store the image locally
            $file->move(storage_path('app/temp'), $fileName);

            // Define the file path in Wasabi bucket
            $filePath = 'profile_pictures/' . $fileName;

            // Define your Wasabi S3 endpoint and credentials
            $endpoint = 'https://s3.us-east-1.wasabisys.com';  // Replace with your Wasabi region endpoint
            $bucketName = 'flapapic';                     // Replace with your Wasabi bucket name
            $region = 'us-east-1';                             // Replace with your Wasabi region
            $accessKey = '0VDI6B63V4LF0TYZK4AX';                      // Replace with your Wasabi access key
            $secretKey = 'k3D7ecACAl5tanPRAaXHa3UThuBu6rj9CY8OtuTm';                      // Replace with your Wasabi secret key

            // Create an S3 client with the specified configuration for Wasabi
            $s3Client = new S3Client([
                'region'     => $region,
                'version'    => 'latest',
                'endpoint'   => $endpoint,
                'credentials' => [
                    'key'    => $accessKey,
                    'secret' => $secretKey,
                ],
            ]);

            // Attempt to upload the local file to the Wasabi bucket
            $result = $s3Client->putObject([
                'Bucket'     => $bucketName,
                'Key'        => $filePath,
                'SourceFile' => $localPath,  // Path to the local file
            ]);

            // Check if the file was successfully uploaded
            if (!isset($result['ObjectURL'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload profile picture to Wasabi.',
                ], 500);
            }

            // Get the file URL from Wasabi
            $fileUrl = $result['ObjectURL'];

            // Update the user's profile picture URL in the database
            $user = UserDetail::where('user_id', $user_id)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Save the file URL to the user's profile
            $user->profile_picture_url = $fileUrl;
            $user->save();

            // Delete the local temporary file
            unlink($localPath);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'file_url' => $fileUrl
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('Failed to update profile picture: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }






    public function updateProfilePictureBKP(Request $request, $user_id)
    {
        try {

            dd($request);
            // Step 1: Validate the incoming request
            $validatedData = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
            ]);

            // Check if validation fails
            if ($validatedData->fails()) {
                return response()->json(['error' => $validatedData->errors()], 400);
            }

            // Step 2: Retrieve the uploaded file
            $file = $request->file('profile_picture');

            // Step 3: Generate a unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Step 4: Store the file using the 'public' disk and profile-pictures directory
            $filePath = $file->storeAs('profile-pic', $filename, 'public');

            // Step 5: Generate the URL to the uploaded file
            $fileUrl = Storage::url($filePath);

            // Optionally, you can update the user's profile picture path in the database here
            $user = UserDetail::where('user_id',$user_id)->first();
            $user->profile_picture_url = $fileUrl; // Assuming you have a profile_picture column
            $user->save();

            // Step 7: Return the uploaded file's path in the response
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'file_path' => $fileUrl
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

