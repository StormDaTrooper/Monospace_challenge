<?php

namespace App\Http\Controllers;

use App\Subscription;
use App\SubscriptionType;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
	    $parameters = [];

	    $active = false;
		$from = request()->input('from');
		$to = request()->input('to');

	    if (request()->input('active') == true) {
		    $active = true;
	    }

		if ($active == true) {
			array_push($parameters, ['to', '>=', now()]);
		}
		if ($active == false) {
			array_push($parameters, ['to', '<', now()]);
		}
		if ($from && $to) {
			array_push($parameters, ['from', '<=', $to]);
			array_push($parameters, ['to', '>=', $from]);
		}

		$subscriptions = $this->getSubscriptions($parameters);

		if (!$subscriptions) {
			return response()->json([],404);
		}

		$data = [];

		foreach ($subscriptions as $subscription) {
			$subscription_type_name = $subscription->getSubscriptionTypeName();

			array_push($data, [
				'type' => $subscription_type_name,
				'price' => $subscription->price,
				'from' => $subscription->from,
				'to' => $subscription->to,
				'user_id' => $subscription->user_id
			]);
		}

		return response()->json($data, 200);
    }

    /**
     * Store a newly created subscription in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	    $data = $this->validateStoreRequest();

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

	    $subscription = Subscription::create([
		    'subscription_type_id' => $data['subscription_type_id'],
		    'user_id' => $data['user_id'],
		    'price' => $price,
		    'from' => now(),
		    'to' => now()->addYear(1)
	    ]);

	    if (!$subscription) {
		    return response('Could not add subscription to the database', 500);
	    }

	    return response($subscription, 200);
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

	/**
	 * @param $user_id
	 *
	 * @return boolean
	 */
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
	 * @param $parameters
	 *
	 * @return mixed
	 */
	public function getSubscriptions($parameters)
	{
		try {
			$subscriptions = DB::table('subscriptions')->where($parameters);

			if (!$subscriptions) {
				return false;
			}

			return $subscriptions;
		} catch (\Exception $exception) {
			return false;
		}
	}

	/**
	 * @return array
	 */
	public function validateStoreRequest(): array
	{
		return request()->validate([
			'subscription_type_id' => 'required',
			'user_id' => 'required',
		]);
	}

	/**
	 * @return array
	 */
	public function validateGetRequest(): array
	{
		return request()->validate([
			'from' => 'required',
			'to' => 'required',
			'active' => 'required',
		]);
	}


}
