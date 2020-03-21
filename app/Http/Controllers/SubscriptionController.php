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

		$subscription_item = $this->getSubscriptionType($data['subscription_type_id']);

	    if (!$subscription_item) {
		    return response('Subscription type not found', 404);
	    }

	    $subscription_is_active = $this->getActiveSubscriptionOfThisType($user->id, $subscription_item->id);

	    if ($subscription_is_active) {
		    return response('There is already and active subscription of this type', 400);
	    }

	    $active_subscription = $this->getActiveSubscriptionOfAnyType($user->id);

	    $price = $subscription_item->price;
	    if ($active_subscription) {
	    	$price = $price - ( $price * (30 / 100));
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
	 * @param $id
	 *
	 * @return bool|Model|\Illuminate\Database\Eloquent\Relations\BelongsTo|object|null
	 */
	public function getSubscriptionType($id)
	{
		try {
			$type = (SubscriptionType::class)
				->where('subscription_type_id', $id)
				->first();

			if (!$type) {
				return false;
			}

			return $type;
		} catch (\Exception $exception) {
			return false;
		}
	}

	/**
	 * @param $user_id
	 * @param $subscription_type_id
	 *
	 * @return boolean
	 */
	public function getActiveSubscriptionOfThisType($user_id, $subscription_type_id)
	{
		try {
			$subscription = Subscription::where([
				['user_id', '=', $user_id],
				['$subscription_type_id', '=', $subscription_type_id],
				['to', '>', now()]
			])
			->first();

			if (!$subscription) {
				return false;
			}

			return true;
		} catch (\Exception $exception) {
			return false;
		}
	}

	public function getActiveSubscriptionOfAnyType($user_id)
	{
		try {
			$subscription = Subscription::where([
				['user_id', '=', $user_id],
				['to', '>', now()]
			])
			->first();

			if (!$subscription) {
				return false;
			}

			return true;
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
