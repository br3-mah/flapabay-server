<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Property;
use App\Models\UserReview;
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
            $properties = Property::get();

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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'county' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'check_in_hour' => 'required|string|max:10',
            'check_out_hour' => 'required|string|max:10',
            'num_of_guests' => 'required|integer',
            'num_of_children' => 'nullable|integer',
            'maximum_guests' => 'required|integer',
            // 'allow_extra_guests' => 'boolean',
            // 'neighborhood_area' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            // 'show_contact_form_instead_of_booking' => 'boolean',
            // 'allow_instant_booking' => 'boolean',
            'currency' => 'required|string|max:10',
            'price_range' => 'required|string|max:50',
            'price' => 'required|numeric',
            // 'price_per_night' => 'required|numeric',
            'additional_guest_price' => 'nullable|numeric',
            'children_price' => 'nullable|numeric',
            'amenities' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'page' => 'nullable|string|max:255',
            'rating' => 'nullable|numeric',
            'favorite' => 'boolean',
            'images' => 'nullable|array',
            'video_link' => 'nullable|url',
            'verified' => 'boolean',
            // 'property_type' => 'required',
            // Add any other fields you need to validate
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

            // Step 2: Insert into Property Model
            $property = Property::create($validatedData->validated());

            // Step 3: Handle image uploads
            $imagePaths = [];
            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    // Store each image and get the path
                    $path = $image->store('properties/images', 'public'); // Store in 'public/properties/images'
                    $imagePaths[] = $path; // Store the path for later use
                }
            }
            // Save image paths to the property (if applicable)
            if (!empty($imagePaths)) {
                $property->images = json_encode($imagePaths); // Store as JSON or adjust as needed
                $property->save();
            }

            // Step 4: Insert into Listing Model
            $listingData = [
                'title' => $request->input('title'), // You can customize this as needed
                'property_id' => $property->id,
                'post_levels' => $request->input('post_levels', null), // Assuming this is optional
                // 'category_id' => $request->input('category_id', null), // Assuming this is optional
                'published_at' => Carbon::now(), // Set to current time or customize as needed
                'status' => 0, // Set default status or customize
            ];

            $listing = Listing::create($listingData);


            DB::commit();
            return response()->json([
                "success" => true,
                "message" => 'Property created successfully',
                "property" => $property,
                "listing" => $listing,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => 'Failed to create property',
                "error" => $e->getMessage(),
            ], 500);
        }
    }


    public function updateProperties(Request $request, $propertyId) {
        // Step 1: Validate incoming request data
        $validatedData = Validator::make($request->all(), [
            'title' => 'nullable',
            'description' => 'nullable',
            'location' => 'nullable',
            'address' => 'nullable',
            'county' => 'nullable',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'check_in_hour'  => 'nullable',
            'check_out_hour' => 'nullable',
            'num_of_guests' => 'nullable',
            'num_of_children' => 'nullable',
            'maximum_guests' => 'nullable',
            'country' => 'nullable',
            'currency' => 'nullable',
            'price_range' => 'nullable',
            'price' => 'nullable',
            'additional_guest_price' => 'nullable',
            'children_price' => 'nullable',
            'amenities' => 'nullable',
            'house_rules' => 'nullable',
            'page' => 'nullable',
            'rating' => 'nullable',
            'favorite' => 'nullable',
            'images' => 'nullable',
            'images.*' => 'nullable', // Validate each image
            'video_link' => 'nullable',
            'verified' => 'nullable'
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
            // Step 2: Fetch the existing property
            $property = Property::where('id',$propertyId)->first();

            // Step 3: Update the property with validated data
            $property->update($validatedData->validated());

            // Step 4: Handle image uploads
            $imagePaths = [];
            if ($request->has('images')) {
                // Optionally delete old images if needed
                if ($property->images) {
                    $oldImages = json_decode($property->images, true);
                    foreach ($oldImages as $oldImage) {
                        Storage::disk('public')->delete($oldImage); // Delete old images
                    }
                }

                foreach ($request->file('images') as $image) {
                    // Store each image and get the path
                    $path = $image->store('properties/images', 'public'); // Store in 'public/properties/images'
                    $imagePaths[] = $path; // Store the path for later use
                }
            }

            // Step 5: Save new image paths to the property (if applicable)
            if (!empty($imagePaths)) {
                $property->images = json_encode($imagePaths); // Store as JSON or adjust as needed
                $property->save();
            }

            // Step 6: Update the listing if necessary
            $listingData = [
                'title' => $request->input('title'), // You can customize this as needed
                'property_id' => $property->id,
                'post_levels' => $request->input('post_levels', null), // Assuming this is optional
                'published_at' => Carbon::now(), // Set to current time or customize as needed
                'status' => 0, // Set default status or customize
            ];

            $listing = Listing::updateOrCreate(
                ['property_id' => $property->id], // Find the listing by property_id
                $listingData // Update or create with this data
            );

            DB::commit();
            return response()->json([
                "success" => true,
                "message" => 'Property updated successfully',
                "property" => $property,
                "listing" => $listing,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => 'Failed to update property',
                "error" => $e->getMessage(),
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

            // Step 2: Find the property
            $property = Property::findOrFail($propertyId);

            // Step 3: Delete related Listings
            Listing::where('property_id', $propertyId)->delete();

            // Step 4: Delete related Availability
            Availability::where('property_id', $propertyId)->delete();

            // Step 5: Delete related Bookings
            Booking::where('property_id', $propertyId)->delete();

            // Step 6: Delete the Property
            $property->delete();

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
            $property = Property::with('listing')->where('id', $propertyId)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

           //return the property model and

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'property' => $property,
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
            // Step 2: Retrieve reviews from UserReview Model where property_id = $propertyId
            $reviews = UserReview::where('property_id', $propertyId)->get();

            // Step 3: Check if reviews exist
            if ($reviews->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No reviews found for this property',
                    'reviews' => [],
                ], 200);
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
            $property = Property::with('listing')->where('id', $propertyId)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

           //return the property model and

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'description' => $property->description,
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
            // Step 2: Retrieve the property from wp_posts
            $property = Property::with('listing')->where('id', $propertyId)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

           //return the property model and

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'price' => $property->price,
                'price_range' => $property->price_range,
                'price_per_night' => $property->price_per_night,
                'additional_guest_price' => $property->additional_guest_price,
                'children_price' => $property->children_price,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve property prices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getPropertyAmenities($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            // Step 2: Retrieve the property from wp_posts
            $property = Property::with('listing')->where('id', $propertyId)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }

           //return the property model and

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'amenities' => $property->amenities,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve amenities',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getAvailabilityDates($propertyId) {
        // Step 1: Validate the property ID
        if (!is_numeric($propertyId) || $propertyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid property ID',
            ], 400);
        }

        try {
            // Step 2: Retrieve availability records for the specified property
            $availabilityRecords = Availability::where('property_id', $propertyId)->get();

            // Step 3: Extract available dates
            $availableDates = [];
            foreach ($availabilityRecords as $record) {
                // Assuming 'availability' is an array of dates
                if (isset($record->availability)) {
                    $availableDates = array_merge($availableDates, $record->availability);
                }
            }

            // Step 4: Return the available dates
            return response()->json([
                'success' => true,
                'message' => 'Availability dates retrieved successfully',
                'available_dates' => array_unique($availableDates), // Remove duplicates
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve availability dates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
