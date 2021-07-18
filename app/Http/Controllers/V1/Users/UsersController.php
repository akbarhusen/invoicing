<?php

namespace Crater\Http\Controllers\V1\Users;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\UserRequest;
use Crater\Models\CompanySetting;
use Crater\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Crater\Models\UserCompany;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->has('limit') ? $request->limit : 10;

        $users = User::where('role', 'admin', 'creator')
            ->applyFilters(
                $request->only([
                    'phone',
                    'email',
                    'display_name',
                    'orderByField',
                    'orderBy',
                ])
            )
            ->latest()
            ->paginate($limit);

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $data['role'] = 'admin';
        $data['company_id'] = Auth::user()->company_id;
        $data['creator_id'] = Auth::id();
        $user = User::create($data);

        if(!empty($request->companies)) {
            foreach($request->companies as $company_id) {
                $userCompany = new UserCompany();
                $userCompany->user_id = $user->id;
                $userCompany->company_id = $company_id;
                $userCompany->save();
            }
        }
        
        $user->setSettings([
            'language' => CompanySetting::getSetting('language', $user->company_id),
        ]);

        return response()->json([
            'user' => $user,
            'success' => true,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Crater\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load('user_companies'),
            'success' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\UserRequest  $request
     * @param  \Crater\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserRequest $request, User $user)
    {
        $user->update($request->validated());
        UserCompany::where('user_id', $user->id)->delete();
        if(!empty($request->companies)) {
            foreach($request->companies as $company_id) {
                $userCompany = new UserCompany();
                $userCompany->user_id = $user->id;
                $userCompany->company_id = $company_id;
                $userCompany->save();
            }
        }

        return response()->json([
            'user' => $user,
            'success' => true,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        if ($request->users) {
            User::destroy($request->users);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
