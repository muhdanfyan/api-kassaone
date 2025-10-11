<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Testimonial;

class TestimonialController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'testimonial_text' => 'required|string',
        ]);

        $testimonial = Testimonial::create([
            'member_id' => $request->user()->id, // Associate with authenticated member
            'testimonial_text' => $request->testimonial_text,
            'is_approved' => false, // Default to not approved
        ]);

        return response()->json($testimonial, 201);
    }
}
