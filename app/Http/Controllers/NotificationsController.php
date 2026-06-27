<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * Get all unread notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->get();
        return response()->json($notifications, 200);
    }

    /**
     * Get all notifications with pagination (read and unread)
     */
    public function all(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(15);
        return response()->json($notifications, 200);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['success' => true, 'message' => 'Notification marked as read'], 200);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true, 'message' => 'All notifications marked as read'], 200);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();
        return response()->json(['success' => true, 'message' => 'Notification deleted'], 200);
    }

    /**
     * Get count of unread notifications
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();
        return response()->json(['unread_count' => $count], 200);
    }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(Notifications $notifications)
    // {
    //     //
    // }
}
