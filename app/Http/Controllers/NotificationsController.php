<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationsRequest;
use App\Http\Requests\UpdateNotificationsRequest;
use App\Models\Notifications;

class NotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    //  the notification we should only see the ones that are linked to the projet of the current user.
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->get();

        return response()->json($notifications,200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNotificationsRequest $request)
    {
        $notification= Notifications::create($request->validated());

        return response()->json($notification,201);
    }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(Notifications $notification)
    // {
        
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificationsRequest $request, Notifications $notification)
    {
        $notification->update($request->validated());

        return response()->json($notification,203);
    }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(Notifications $notifications)
    // {
    //     //
    // }
}
