<?php

namespace App\Http\Controllers;

use App\Subscription;
use App\SubscriptionType;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Store a newly created subscription in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	    $data = $this->validateRequest();
	    $subscription = new Subscription();

	    $user = $this->getUser($data);

	    if (!$user) {
	    	return response('User not found or is not activated', 404);
	    }

	    return response('', 200);
    }

	public function getUser($data)
	{
		try {
			$client = new Client();

			$response = $client->request('GET', 'http://vaggelis.users.challenge.dev.monospacelabs.com/users');
			$users = $response->getBody();
			$found_user = '';

			foreach ($users as $user) {
				if ($user->id == $data['user_id']) {
					$found_user = $user;
				}
			}

			if (!$found_user) {
				return false;
			}
			if (!$found_user->active) {
				return false;
			}

			return $found_user;
		} catch (\Exception $exception) {
			return false;
		}
	}

	/**
	 * @return array
	 */
	public function validateRequest(): array
	{
		return request()->validate([
			'subscription_type_id' => 'required',
			'user_id' => 'required',
		]);
	}


}
