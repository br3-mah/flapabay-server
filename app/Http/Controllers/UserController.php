<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
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

