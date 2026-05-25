<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    /**
     * Get all communities (public list dengan info apakah user sudah join).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Get all communities dengan creator info
        $communities = Community::with('creator')
            ->withCount([
                'members as members_count' => function ($query) {
                    $query->where('status', 'approved');
                },
            ])
            ->paginate(20);

        // Transform untuk menambahkan info apakah user sudah join
        $data = $communities->map(function ($community) use ($user) {
            return [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'capacity' => $community->capacity,
                'location' => $community->location,
                'privacy' => $community->privacy,
                'permission' => $community->permission,
                'cover_image' => $community->cover_image,
                'created_by' => $community->created_by,
                'creator_id' => $community->created_by,
                'creator' => [
                    'id' => $community->creator->id,
                    'email' => $community->creator->email,
                ],
                'members_count' => $community->members_count,
                'is_member' => $community->isMember($user),
                'user_role' => $community->getMemberRole($user),
                'created_at' => $community->created_at->toIso8601String(),
                'updated_at' => $community->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $communities->currentPage(),
                'per_page' => $communities->perPage(),
                'total' => $communities->total(),
                'last_page' => $communities->lastPage(),
            ],
        ]);
    }

    /**
     * Get communities created by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myCreatedCommunities(): JsonResponse
    {
        $user = Auth::user();

        $communities = Community::where('created_by', $user->id)
            ->withCount([
                'members as members_count' => function ($query) {
                    $query->where('status', 'approved');
                },
            ])
            ->get()
            ->map(function ($community) {
                return [
                    'id' => $community->id,
                    'name' => $community->name,
                    'description' => $community->description,
                    'capacity' => $community->capacity,
                    'location' => $community->location,
                    'privacy' => $community->privacy,
                    'permission' => $community->permission,
                    'cover_image' => $community->cover_image,
                    'creator_id' => $community->created_by,
                    'members_count' => $community->members_count,
                    'created_at' => $community->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $communities,
        ]);
    }

    /**
     * Get communities joined by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myJoinedCommunities(): JsonResponse
    {
        $user = Auth::user();

        $communities = CommunityMember::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('community')
            ->get()
            ->map(function ($membership) {
                return [
                    'id' => $membership->community->id,
                    'name' => $membership->community->name,
                    'description' => $membership->community->description,
                    'capacity' => $membership->community->capacity,
                    'location' => $membership->community->location,
                    'privacy' => $membership->community->privacy,
                    'permission' => $membership->community->permission,
                    'cover_image' => $membership->community->cover_image,
                    'creator_id' => $membership->community->created_by,
                    'user_role_in_community' => $membership->role,
                    'joined_at' => $membership->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $communities,
        ]);
    }

    /**
     * Get detail komunitas dengan list members.
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Community $community): JsonResponse
    {
        $user = Auth::user();

        $members = $community->members()
            ->where('status', 'approved')
            ->with('user')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'email' => $member->user->email,
                    'role' => $member->role,
                    'joined_at' => $member->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'capacity' => $community->capacity,
                'location' => $community->location,
                'privacy' => $community->privacy,
                'permission' => $community->permission,
                'cover_image' => $community->cover_image,
                'created_by' => $community->created_by,
                'creator_id' => $community->created_by,
                'creator_email' => $community->creator->email,
                'creator_name' => $community->creator?->profile?->name,
                'members_count' => $members->count(),
                'members' => $members,
                'is_member' => $community->isMember($user),
                'user_role' => $community->getMemberRole($user),
                'created_at' => $community->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create komunitas baru (hanya influencer yang bisa).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate request
        // Location harus include provinsi: format "lokasi, kota, provinsi, negara"
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'location' => ['required', 'string', 'min:10', 'max:255'], // Min 10 for format with province
            'privacy' => ['nullable', 'string', 'in:Publik,Privat'],
            'permission' => ['nullable', 'string', 'in:Perizinan Admin,Bebas'],
            'cover_image' => ['nullable', 'string', 'max:255'],
        ], [
            'location.required' => 'Lokasi komunitas harus diisi dengan format lengkap termasuk provinsi (contoh: Sukolilo, Surabaya, Jawa Timur, Indonesia)',
            'location.min' => 'Lokasi harus include provinsi lengkap (contoh: Sukolilo, Surabaya, Jawa Timur, Indonesia)',
        ]);

        $community = DB::transaction(function () use ($validated, $user) {
            $community = Community::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'capacity' => $validated['capacity'] ?? 0,
                'location' => $validated['location'], // Required, tidak punya null fallback
                'privacy' => $validated['privacy'] ?? 'Publik',
                'permission' => $validated['permission'] ?? 'Bebas',
                'cover_image' => $validated['cover_image'] ?? null,
                'created_by' => $user->id,
            ]);

            // Creator otomatis member approved dengan role admin.
            CommunityMember::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $community;
        });

        return response()->json([
            'success' => true,
            'message' => 'Community created successfully',
            'data' => [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'capacity' => $community->capacity,
                'location' => $community->location,
                'privacy' => $community->privacy,
                'permission' => $community->permission,
                'cover_image' => $community->cover_image,
                'created_by' => $community->created_by,
                'creator_id' => $community->created_by,
                'created_at' => $community->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Join komunitas (request membership).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function join(Request $request, Community $community): JsonResponse
    {
        $user = Auth::user();

        $membership = CommunityMember::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership && $membership->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'You are already a member of this community',
            ], 400);
        }

        $isPrivate = $community->privacy === 'Privat';
        $requiresAdminApproval = $community->permission === 'Perizinan Admin';

        // Private community: hanya bisa join via invitation.
        if ($isPrivate) {
            if (!$membership || $membership->status !== 'invited') {
                return response()->json([
                    'success' => false,
                    'message' => 'This is a private community. You need an invitation to join.',
                ], 403);
            }

            $membership->update([
                'status' => 'approved',
                'role' => 'pegiat',
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the private community',
                'data' => [
                    'community_id' => $community->id,
                    'community_name' => $community->name,
                    'role' => 'pegiat',
                    'membership_status' => 'approved',
                    'joined_at' => now()->toIso8601String(),
                ],
            ], 201);
        }

        // Public + admin approval: join request masuk pending.
        if ($requiresAdminApproval) {
            if ($membership && $membership->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your join request is still pending admin approval',
                ], 409);
            }

            if ($membership) {
                $membership->update([
                    'status' => 'pending',
                    'role' => 'pegiat',
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            } else {
                CommunityMember::create([
                    'community_id' => $community->id,
                    'user_id' => $user->id,
                    'role' => 'pegiat',
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Join request sent. Waiting for admin approval.',
                'data' => [
                    'community_id' => $community->id,
                    'community_name' => $community->name,
                    'membership_status' => 'pending',
                ],
            ], 202);
        }

        // Public + bebas: langsung approved.
        if ($membership) {
            $membership->update([
                'status' => 'approved',
                'role' => 'pegiat',
                'approved_at' => now(),
            ]);
        } else {
            CommunityMember::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'role' => 'pegiat',
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined the community',
            'data' => [
                'community_id' => $community->id,
                'community_name' => $community->name,
                'role' => 'pegiat',
                'membership_status' => 'approved',
                'joined_at' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Invite user to a private/public community.
     */
    public function invite(Request $request, Community $community): JsonResponse
    {
        $actor = Auth::user();

        if (!$this->canManageCommunity($community->id, $actor->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can invite members',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetUserId = (int) $validated['user_id'];

        if ($targetUserId === (int) $community->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'Community creator is already part of the community',
            ], 400);
        }

        $membership = CommunityMember::where('community_id', $community->id)
            ->where('user_id', $targetUserId)
            ->first();

        if ($membership && $membership->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'User is already an approved member',
            ], 409);
        }

        if ($membership) {
            $membership->update([
                'status' => 'invited',
                'role' => $membership->role === 'admin' ? 'admin' : 'pegiat',
                'invited_by' => $actor->id,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        } else {
            CommunityMember::create([
                'community_id' => $community->id,
                'user_id' => $targetUserId,
                'role' => 'pegiat',
                'status' => 'invited',
                'invited_by' => $actor->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent',
            'data' => [
                'community_id' => $community->id,
                'user_id' => $targetUserId,
                'status' => 'invited',
            ],
        ], 201);
    }

    /**
     * List join requests that need approval.
     */
    public function joinRequests(Community $community): JsonResponse
    {
        $actor = Auth::user();

        if (!$this->canManageCommunity($community->id, $actor->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can view join requests',
            ], 403);
        }

        $requests = CommunityMember::where('community_id', $community->id)
            ->whereIn('status', ['pending'])
            ->with('user.profile')
            ->latest('created_at')
            ->get()
            ->map(function ($member) {
                return [
                    'member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'email' => $member->user?->email,
                    'name' => $member->user?->profile?->name,
                    'status' => $member->status,
                    'requested_at' => $member->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Approve pending join request.
     */
    public function approveJoinRequest(Community $community, CommunityMember $member): JsonResponse
    {
        $actor = Auth::user();

        if (!$this->canManageCommunity($community->id, $actor->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can approve members',
            ], 403);
        }

        if ((int) $member->community_id !== (int) $community->id) {
            return response()->json([
                'success' => false,
                'message' => 'Member does not belong to this community',
            ], 422);
        }

        $member->update([
            'status' => 'approved',
            'role' => $member->role === 'admin' ? 'admin' : 'pegiat',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Join request approved',
            'data' => [
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'status' => $member->status,
            ],
        ]);
    }

    /**
     * Reject pending join request.
     */
    public function rejectJoinRequest(Community $community, CommunityMember $member): JsonResponse
    {
        $actor = Auth::user();

        if (!$this->canManageCommunity($community->id, $actor->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can reject members',
            ], 403);
        }

        if ((int) $member->community_id !== (int) $community->id) {
            return response()->json([
                'success' => false,
                'message' => 'Member does not belong to this community',
            ], 422);
        }

        if ($member->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending join requests can be rejected',
            ], 422);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Join request rejected',
        ]);
    }

    /**
     * Leave komunitas.
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave(Community $community): JsonResponse
    {
        $user = Auth::user();

        // Check if user is member
        if (!$community->isMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this community',
            ], 400);
        }

        // Check if creator (cannot leave jika creator)
        if ($community->created_by === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Creator cannot leave the community. Delete community instead.',
            ], 400);
        }

        // Remove membership
        CommunityMember::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully left the community',
        ]);
    }

    /**
     * Get members dari komunitas.
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function members(Community $community): JsonResponse
    {
        $actor = Auth::user();

        if (!$this->canManageCommunity($community->id, $actor->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can view member list',
            ], 403);
        }

        $members = $community->members()
            ->where('status', 'approved')
            ->with('user', 'user.profile')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'email' => $member->user->email,
                    'name' => $member->user?->profile?->name,
                    'photo_profile' => $member->user?->profile?->photo_profile,
                    'role' => $member->role,
                    'status' => $member->status,
                    'joined_at' => $member->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Update member role (hanya creator/influencer yang bisa).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Community  $community
     * @param  \App\Models\CommunityMember  $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMemberRole(Request $request, Community $community, CommunityMember $member): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageCommunity($community->id, $user->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can manage members',
            ], 403);
        }

        if ((int) $member->community_id !== (int) $community->id) {
            return response()->json([
                'success' => false,
                'message' => 'Member does not belong to this community',
            ], 422);
        }

        if ($member->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved members can have roles updated',
            ], 422);
        }

        if ((int) $member->user_id === (int) $community->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'Community creator role cannot be changed',
            ], 400);
        }

        // Validate request
        $validated = $request->validate([
            'role' => ['required', 'in:influencer,admin,pegiat'],
        ]);

        // Update role
        $member->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Member role updated',
            'data' => [
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'role' => $member->role,
            ],
        ]);
    }

    /**
     * Remove member dari komunitas (hanya creator/influencer yang bisa).
     *
     * @param  \App\Models\Community  $community
     * @param  \App\Models\CommunityMember  $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Community $community, CommunityMember $member): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageCommunity($community->id, $user->id, $community->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator/admin can manage members',
            ], 403);
        }

        if ((int) $member->community_id !== (int) $community->id) {
            return response()->json([
                'success' => false,
                'message' => 'Member does not belong to this community',
            ], 422);
        }

        // Cannot remove creator
        if ($member->user_id === $community->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove community creator',
            ], 400);
        }

        // Delete membership
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member removed from community',
        ]);
    }

    /**
     * Delete komunitas (hanya creator yang bisa).
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Community $community): JsonResponse
    {
        $user = Auth::user();

        // Check if user is creator
        if ($community->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only community creator can delete',
            ], 403);
        }

        $community->delete(); // Cascade delete akan remove members dan messages

        return response()->json([
            'success' => true,
            'message' => 'Community deleted successfully',
        ]);
    }

    private function canManageCommunity(int $communityId, int $userId, int $creatorId): bool
    {
        if ($userId === $creatorId) {
            return true;
        }

        return CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereIn('role', ['influencer', 'admin'])
            ->exists();
    }
}
