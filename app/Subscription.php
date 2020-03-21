<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = [];

    public function getSubscriptionTypeName()
    {
    	return ($this->subscriptionType()->first())->name;
    }

	public function subscriptionType()
	{
		return $this->belongsTo(SubscriptionType::class);
	}
}
