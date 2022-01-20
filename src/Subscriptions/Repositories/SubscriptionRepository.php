<?php

namespace Give\Subscriptions\Repositories;

use Give\Subscriptions\DataTransferObjects\SubscriptionObjectData;
use Give\Subscriptions\Models\Subscription;
use Give_Subscriptions_DB;

class SubscriptionRepository
{
    /**
     * @unreleased
     *
     * @param  int  $subscriptionId
     * @return Subscription
     */
    public function getById($subscriptionId)
    {
        /** @var Give_Subscriptions_DB $db */
        $db = give(Give_Subscriptions_DB::class);

        $subscriptionObject = $db->get($subscriptionId);

        return SubscriptionObjectData::fromObject($subscriptionObject)->toSubscription();
    }

    /**
     * @unreleased
     *
     * @param  int  $donationId
     * @return Subscription
     */
    public function getByDonationId($donationId)
    {
        /** @var Give_Subscriptions_DB $db */
        $db = give(Give_Subscriptions_DB::class);

        $subscriptionObject = $db->get_by('parent_payment_id', $donationId);

        return SubscriptionObjectData::fromObject($subscriptionObject)->toSubscription();
    }

    /**
     * @unreleased
     *
     * @param  int  $donorId
     * @return array|Subscription[]
     */
    public function getByDonorId($donorId)
    {
        global $wpdb;

        $query = $wpdb->get_results(
            "SELECT *
                    FROM {$wpdb->prefix}give_subscriptions
                    WHERE customer_id = $donorId"
        );

        return array_map(static function ($object) {
            return SubscriptionObjectData::fromObject($object)->toSubscription();
        }, $query);
    }

}
