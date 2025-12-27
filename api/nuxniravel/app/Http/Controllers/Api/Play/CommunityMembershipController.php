<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;

use App\Models\CommunityMembership;
use App\Http\Requests\StoreCommunityMembershipRequest;
use App\Http\Requests\UpdateCommunityMembershipRequest;

class CommunityMembershipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommunityMembershipRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CommunityMembership $communityMembership)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommunityMembership $communityMembership)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommunityMembershipRequest $request, CommunityMembership $communityMembership)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommunityMembership $communityMembership)
    {
        //
    }
}
