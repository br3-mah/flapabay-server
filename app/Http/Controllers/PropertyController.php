<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    /**
     * Get a list of properties without filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProperties(Request $request)
    {
        try {
            // Base query to get properties from wp_posts and join with wp_aioseo_posts
            $properties = DB::table('wp_posts')
                ->select(
                    'wp_posts.ID as property_id',
                    'wp_posts.post_title',
                    'wp_posts.post_content',
                    'wp_posts.post_date',
                    'wp_posts.guid as property_url',
                    'wp_aioseo_posts.og_image_url as image_url',
                    'wp_aioseo_posts.og_description as seo_description',
                    'wp_aioseo_posts.seo_score',
                    'wp_posts.post_status',
                    'wp_posts.post_type',
                    'wp_posts.comment_count',
                    'wp_aioseo_posts.images as images'
                )->leftJoin('wp_aioseo_posts', 'wp_posts.ID', '=', 'wp_aioseo_posts.post_id')
                ->where('wp_posts.post_type', 'estate_property')
                ->where('wp_posts.post_status', 'publish')
                ->get();

            // Convert concatenated images string into an array for each property
            $properties->transform(function ($property) {
                $property->images = $property->images ? explode(',', $property->images) : [];
                return $property;
            });

            // Return success response with data
            return response()->json([
                'success' => true,
                'data' => $properties,
                'total_results' => count($properties),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new property and store it in wp_posts and wp_postmeta.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createProperties(Request $request) {
        // Step 1: Validate incoming request data
        $validatedData = Validator::make($request->all(), [
            'host_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'rating' => 'nullable|numeric|min:0|max:5',
            'favorite' => 'nullable|boolean',
            'verified' => 'nullable|boolean',
            'property_type' => 'nullable|string',
            // Uncomment below if photo uploads are needed
            // 'photos' => 'nullable|array',
            // 'photos.*' => 'nullable|file|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validatedData->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Step 2: Handle photo uploads
            $photoUrls = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('properties/photos', 'public');
                    $photoUrls[] = Storage::url($path);
                }
            }

            // Step 3: Insert the new property into wp_posts table
            $propertyId = DB::table('wp_posts')->insertGetId([
                'post_author' => $request->input('host_id'),
                'post_date' => Carbon::now(),
                'post_date_gmt' => Carbon::now()->utc(),
                'post_content' => $request->input('description'),
                'post_title' => $request->input('name'),
                'post_excerpt' => substr($request->input('description'), 0, 100),
                'post_status' => 'publish',
                'comment_status' => 'open',
                'ping_status' => 'closed',
                'post_name' => strtolower(str_replace(' ', '-', $request->input('name'))),
                'post_modified' => Carbon::now(),
                'post_modified_gmt' => Carbon::now()->utc(),
                'post_type' => 'attachment',
                'to_ping' => 'attachment',
                'pinged' => 'attachment',
                'post_content_filtered' => 'attachment',
                'comment_count' => 0
                // Additional fields can be added as needed
            ]);

            // Step 4: Insert metadata for the new property in wp_postmeta table
            $metaData = [
                ['post_id' => $propertyId, 'meta_key' => '_wp_attached_file',
                 'meta_value' => isset($photoUrls[0]) ? $photoUrls[0] : null],
                ['post_id' => $propertyId,
                 'meta_key' => '_wp_attachment_metadata',
                 'meta_value' => serialize(['photos' => $photoUrls])],
                ['post_id' => $propertyId,
                 'meta_key' => '_wp_attachment_backup_sizes',
                 'meta_value' => serialize(['full-orig' => ['width' => 1920, 'height' => 1080]])],
                ['post_id' => $propertyId,
                 'meta_key' => 'rating',
                 'meta_value' => $request->input('rating', 0)],
                ['post_id' => $propertyId,
                 'meta_key' => 'favorite',
                 'meta_value' => $request->input('favorite', false) ? 1 : 0],
                ['post_id' => $propertyId,
                 'meta_key' => 'verified',
                 'meta_value' => $request->input('verified', false) ? 1 : 0],
                ['post_id' => $propertyId,
                 'meta_key' => 'price',
                 'meta_value' => $request->input('price')],
                ['post_id' => $propertyId,
                 'meta_key' => 'location',
                 'meta_value' => $request->input('location')],
                ['post_id' => $propertyId,
                 'meta_key' => 'property_type',
                 'meta_value' => $request->input('property_type', '')],
            ];

            DB::table('wp_postmeta')->insert($metaData);

            // Step 5: Insert into wp_aioseo_posts table for SEO purposes
            if (!empty($photoUrls)) {
                // Format image URLs for AIOSEO
                $imagesJson = json_encode(array_map(function($url) {
                    return ["image:loc" => $url];
                }, $photoUrls));

                DB::table('wp_aioseo_posts')->insert([
                    [
                        "post_id" => $propertyId,
                        "images" => $imagesJson,
                        // Additional fields can be populated as needed
                    ]
                ]);
            }

            // Step 6: Retrieve the full property details
            $property = DB::table('wp_posts')->where('ID', $propertyId)->first();

            // Prepare property details for response
            $propertyDetails = [
                "id" =>$property->ID,
                "host_id" =>$request->input("host_id"),
                "name" =>$property->post_title,
                "location" =>$request->input("location"),
                "description" =>$property->post_content,
                "price" =>$request->input("price"),
                "photo_urls" =>$photoUrls,
                "rating" =>$request->input("rating", 0),
                "favorite" =>$request->input("favorite", false),
                "verified" =>$request->input("verified", false),
                "property_type" =>$request->input("property_type", ''),
                "post_date" =>$property->post_date,
                "status" =>$property->post_status,
                "slug" =>$property->post_name,
               // Add any other necessary fields here
           ];

           DB::commit();
           return response()->json([
               "success" => true,
               "message" =>'Property created successfully',
               "property" =>$propertyDetails,
           ], 201);

       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json([
               "success" => false,
               "message" =>'Failed to create property',
               "error" =>$e->getMessage(),
           ], 500);
       }
    }

    public function updateProperties(Request $request, $propertyId) {

        // dd($request);
        // Step 1: Validate incoming request data
        $validatedData = Validator::make($request->all(), [
            'host_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'rating' => 'nullable|numeric|min:0|max:5',
            'favorite' => 'nullable|boolean',
            'verified' => 'nullable|boolean',
            'property_type' => 'nullable|string',
            // Uncomment below if photo uploads are needed
            // 'photos' => 'nullable|array',
            // 'photos.*' => 'nullable|file|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validatedData->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Step 2: Handle photo uploads
            $photoUrls = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('properties/photos', 'public');
                    $photoUrls[] = Storage::url($path);
                }
            }

            // Step 3: Update the existing property in wp_posts table
            DB::table('wp_posts')->where('ID', $propertyId)->update([
                'post_author' => $request->input('host_id'),
                'post_content' => $request->input('description'),
                'post_title' => $request->input('name'),
                'post_excerpt' => substr($request->input('description'), 0, 100),
                'post_modified' => Carbon::now(),
                'post_modified_gmt' => Carbon::now()->utc(),
                // Additional fields can be updated as needed
            ]);

            // Step 4: Update metadata for the property in wp_postmeta table
            $metaData = [
                ['post_id' => $propertyId,
                 'meta_key' => '_wp_attached_file',
                 'meta_value' => isset($photoUrls[0]) ? $photoUrls[0] : null],
                ['post_id' => $propertyId,
                 'meta_key' => '_wp_attachment_metadata',
                 'meta_value' => serialize(['photos' => $photoUrls])],
                ['post_id' => $propertyId,
                 'meta_key' => '_wp_attachment_backup_sizes',
                 'meta_value' => serialize(['full-orig' => ['width' => 1920, 'height' => 1080]])],
                ['post_id' => $propertyId,
                 'meta_key' => 'rating',
                 'meta_value' => $request->input('rating', 0)],
                ['post_id' => $propertyId,
                 'meta_key' => 'favorite',
                 'meta_value' => $request->input('favorite', false) ? 1 : 0],
                ['post_id' => $propertyId,
                 'meta_key' => 'verified',
                 'meta_value' => $request->input('verified', false) ? 1 : 0],
                ['post_id' => $propertyId,
                 'meta_key' => 'price',
                 'meta_value' => $request->input('price')],
                ['post_id' => $propertyId,
                 'meta_key' => 'location',
                 'meta_value' => $request->input('location')],
                ['post_id' => $propertyId,
                 'meta_key' => 'property_type',
                 'meta_value' => $request->input('property_type', '')],
            ];

            // Delete existing meta data for the property before inserting updated data
            DB::table('wp_postmeta')->where('post_id', $propertyId)->delete();

            DB::table('wp_postmeta')->insert($metaData);

            // Step 5: Update into wp_aioseo_posts table for SEO purposes
            if (!empty($photoUrls)) {
                // Format image URLs for AIOSEO
                $imagesJson = json_encode(array_map(function($url) {
                    return ["image:loc" => $url];
                }, $photoUrls));

                DB::table('wp_aioseo_posts')->updateOrInsert(
                    ['post_id' => $propertyId],
                    [
                        "images" => $imagesJson,
                        "og_object_type" => 'default',
                        "og_image_type" => 'default',
                        'schema' => '{"blockGraphs":[],"customGraphs":[],"default":{"data":{"Article":[],"Course":[],"Dataset":[],"FAQPage":[],"Movie":[],"Person":[],"Product":[],"Recipe":[],"Service":[],"SoftwareApplication":[],"WebPage":[]},"graphName":"WebPage","isEnabled":true},"graphs":[]}',
                        'schema_type' => 'default'
                    ]
                );
            }

            // Step 6: Retrieve the full property details
            $property = DB::table('wp_posts')->where('ID', $propertyId)->first();

            // Prepare property details for response
            if ($property) {
                $propertyDetails = [
                    "id" =>$property->ID,
                    "host_id" =>$request->input("host_id"),
                    "name" =>$property->post_title,
                    "location" =>$request->input("location"),
                    "description" =>$property->post_content,
                    "price" =>$request->input("price"),
                    "photo_urls" =>$photoUrls,
                    "rating" =>$request->input("rating", 0),
                    "favorite" =>$request->input("favorite", false),
                    "verified" =>$request->input("verified", false),
                    "property_type" =>$request->input("property_type", ''),
                    "post_date" =>$property->post_date,
                    "status" =>$property->post_status,
                    "slug" =>$property->post_name,
                   // Add any other necessary fields here
               ];

               DB::commit();
               return response()->json([
                   "success" => true,
                   "message" =>'Property updated successfully',
                   "property" =>$propertyDetails,
               ], 200);
           } else {
               DB::rollBack();
               return response()->json([
                   "success" => false,
                   "message" =>'Property not found',
               ], 404);
           }

       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json([
               "success" => false,
               "message" =>'Failed to update property',
               "error" =>$e->getMessage(),
           ], 500);
       }
    }


    public function deleteProperty($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Step 2: Check if the property exists
            $property = DB::table('wp_posts')->where('ID', $propertyId)->first();
            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

            // Step 3: Delete associated metadata from wp_postmeta
            DB::table('wp_postmeta')->where('post_id', $propertyId)->delete();

            // Step 4: Delete associated SEO data from wp_aioseo_posts
            DB::table('wp_aioseo_posts')->where('post_id', $propertyId)->delete();

            // Step 5: Delete the property from wp_posts
            DB::table('wp_posts')->where('ID', $propertyId)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Property deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function getProperty($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            // Step 2: Retrieve the property from wp_posts
            $property = DB::table('wp_posts')->where('ID', $propertyId)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

            // Step 3: Retrieve associated metadata from wp_postmeta
            $metaData = DB::table('wp_postmeta')->where('post_id', $propertyId)->get();

            // Step 4: Retrieve SEO data from wp_aioseo_posts
            $seoData = DB::table('wp_aioseo_posts')->where('post_id', $propertyId)->first();

            // Step 5: Prepare property details for response
            $propertyDetails = [
                'id' => $property->ID,
                'host_id' => $property->post_author,
                'name' => $property->post_title,
                'location' => null, // Default to null, will be populated from meta data
                'description' => $property->post_content,
                'price' => null, // Default to null, will be populated from meta data
                'photo_urls' => [], // Default to empty array, will be populated from meta data
                'rating' => null, // Default to null, will be populated from meta data
                'favorite' => null, // Default to null, will be populated from meta data
                'verified' => null, // Default to null, will be populated from meta data
                'property_type' => null, // Default to null, will be populated from meta data
                'post_date' => $property->post_date,
                'status' => $property->post_status,
                'slug' => $property->post_name,
                'guid' => $property->guid,
                'seo_data' => (array)$seoData, // Include SEO data in response
            ];

            // Step 6: Map metadata to property details
            foreach ($metaData as $meta) {
                switch ($meta->meta_key) {
                    case 'location':
                        $propertyDetails['location'] = $meta->meta_value;
                        break;
                    case 'price':
                        $propertyDetails['price'] = $meta->meta_value;
                        break;
                    case '_wp_attached_file':
                        if ($meta->meta_value) {
                            $propertyDetails['photo_urls'][] = Storage::url($meta->meta_value);
                        }
                        break;
                    case 'rating':
                        $propertyDetails['rating'] = (int)$meta->meta_value;
                        break;
                    case 'favorite':
                        $propertyDetails['favorite'] = (bool)$meta->meta_value;
                        break;
                    case 'verified':
                        $propertyDetails['verified'] = (bool)$meta->meta_value;
                        break;
                    case 'property_type':
                        $propertyDetails['property_type'] = $meta->meta_value;
                        break;
                    default:
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'property' => $propertyDetails,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getPropertyReviews($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            // Step 2: Retrieve comments for the specified property
            $comments = DB::table('wp_comments')
                ->where('comment_post_ID', $propertyId)
                ->where('comment_approved', '1') // Only approved comments
                ->get();

            if ($comments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No reviews found for this property',
                    'reviews' => [],
                ], 200);
            }

            // Step 3: Prepare reviews array
            $reviews = [];
            foreach ($comments as $comment) {
                // Step 4: Retrieve comment meta (ratings)
                $ratingMeta = DB::table('wp_commentmeta')
                    ->where('comment_id', $comment->comment_ID)
                    ->where('meta_key', 'review_stars') // Assuming this is the key for ratings
                    ->first();

                // Prepare review data
                $reviewData = [
                    'id' => $comment->comment_ID,
                    'author' => $comment->comment_author,
                    'author_email' => $comment->comment_author_email,
                    'content' => $comment->comment_content,
                    'date' => $comment->comment_date,
                    'rating' => null, // Default to null if no rating found
                ];

                // If rating meta exists, decode it and add to review data
                if ($ratingMeta) {
                    $reviewData['rating'] = json_decode($ratingMeta->meta_value);
                }

                $reviews[] = $reviewData;
            }

            return response()->json([
                'success' => true,
                'message' => 'Reviews retrieved successfully',
                'reviews' => $reviews,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getPropertyDescription($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            // Step 2: Retrieve the property from wp_posts
            $property = DB::table('wp_posts')->where('ID', $propertyId)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

            // Step 3: Retrieve associated SEO data from wp_aioseo_posts
            $seoData = DB::table('wp_aioseo_posts')->where('post_id', $propertyId)->first();

            // Step 4: Prepare response data
            $responseData = [
                'id' => $property->ID,
                'title' => $property->post_title,
                'description' => $property->post_content,
                'seo_description' => $seoData ? $seoData->description : null,
                'images' => $seoData ? json_decode($seoData->images) : [], // Decode images JSON if available
            ];

            return response()->json([
                'success' => true,
                'message' => 'Property description retrieved successfully',
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve property description',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getPropertyPriceDetails($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            // Step 2: Retrieve price details from wp_postmeta
            $priceMeta = DB::table('wp_postmeta')
                ->where('post_id', $propertyId)
                ->where('meta_key', 'price') // Assuming 'price' is the key for price details
                ->first();

            if (!$priceMeta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Price details not found for this property',
                ], 404);
            }

            // Step 3: Prepare response data
            $responseData = [
                'property_id' => $propertyId,
                'price' => $priceMeta->meta_value,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Price details retrieved successfully',
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
