<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|City query()
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereUpdatedAt($value)
 */
	class City extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $service_request_id
 * @property int|null $job_id
 * @property int $participant_1_id
 * @property int $participant_2_id
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Job|null $job
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $participant1
 * @property-read \App\Models\User $participant2
 * @property-read \App\Models\ServiceRequest|null $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereParticipant1Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereParticipant2Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereUpdatedAt($value)
 */
	class Conversation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $address
 * @property int $city_id
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $average_rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Technician> $favoriteTechnicians
 * @property-read int|null $favorite_technicians_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Favorite> $favorites
 * @property-read int|null $favorites_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Job> $jobs
 * @property-read int|null $jobs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceRequest> $serviceRequests
 * @property-read int|null $service_requests_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereAverageRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUserId($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property int $technician_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Technician $technician
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite query()
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereUpdatedAt($value)
 */
	class Favorite extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $job_number
 * @property int $service_request_id
 * @property int $job_offer_id
 * @property int $customer_id
 * @property int $technician_id
 * @property string $agreed_price
 * @property string|null $final_price
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation|null $conversation
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\JobOffer $offer
 * @property-read \App\Models\Payment|null $payment
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @property-read \App\Models\Technician $technician
 * @method static \Illuminate\Database\Eloquent\Builder|Job newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Job newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Job query()
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereAgreedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereFinalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereJobNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereJobOfferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereUpdatedAt($value)
 */
	class Job extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $service_request_id
 * @property int $technician_id
 * @property string $offered_price
 * @property int|null $estimated_duration
 * @property string|null $notes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Job|null $job
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @property-read \App\Models\Technician $technician
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer query()
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereEstimatedDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereOfferedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobOffer whereUpdatedAt($value)
 */
	class JobOffer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string $body
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User $sender
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereSenderId($value)
 */
	class Message extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property array|null $data
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereUserId($value)
 */
	class Notification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $payment_number
 * @property int $job_id
 * @property int $customer_id
 * @property int $technician_id
 * @property string $amount
 * @property string $commission_amount
 * @property string $technician_earnings
 * @property string $payment_method
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_charge_id
 * @property string|null $stripe_client_secret
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Job $job
 * @property-read \App\Models\Technician $technician
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCommissionAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStripeChargeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStripeClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStripePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTechnicianEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $job_id
 * @property int $customer_id
 * @property int $technician_id
 * @property int $rating
 * @property string|null $comment
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Job $job
 * @property-read \App\Models\Technician $technician
 * @method static \Illuminate\Database\Eloquent\Builder|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereUpdatedAt($value)
 */
	class Review extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $name_ar
 * @property string|null $icon
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceRequest> $serviceRequests
 * @property-read int|null $service_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Technician> $technicians
 * @property-read int|null $technicians_count
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereNameAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceCategory whereUpdatedAt($value)
 */
	class ServiceCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $service_request_id
 * @property string $path
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $ai_checked_at
 * @property string|null $ai_result
 * @property string|null $ai_confidence_score
 * @property array|null $ai_detected_objects
 * @property string|null $ai_suggested_service
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereAiCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereAiConfidenceScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereAiDetectedObjects($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereAiResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereAiSuggestedService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceImage whereUpdatedAt($value)
 */
	class ServiceImage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $request_number
 * @property int $customer_id
 * @property int $category_id
 * @property string $title
 * @property string $description
 * @property string $address
 * @property int $city_id
 * @property string|null $latitude
 * @property string|null $longitude
 * @property \Illuminate\Support\Carbon|null $preferred_date
 * @property string|null $preferred_time
 * @property string|null $status
 * @property string|null $ai_predicted_price
 * @property string $customer_proposed_price Price chosen/customized by the customer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceCategory $category
 * @property-read \App\Models\City $city
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceImage> $images
 * @property-read int|null $images_count
 * @property-read \App\Models\Job|null $job
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JobOffer> $offers
 * @property-read int|null $offers_count
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereAiPredictedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereCustomerProposedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest wherePreferredDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest wherePreferredTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereRequestNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceRequest whereUpdatedAt($value)
 */
	class ServiceRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $service_category_id
 * @property int|null $years_of_experience
 * @property string|null $bio
 * @property int $city_id
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $verification_status
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string $average_rating
 * @property int $total_jobs_completed
 * @property bool $is_available
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceCategory $category
 * @property-read \App\Models\City $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TechnicianDocument> $documents
 * @property-read int|null $documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $favoritedBy
 * @property-read int|null $favorited_by_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JobOffer> $jobOffers
 * @property-read int|null $job_offers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Job> $jobs
 * @property-read int|null $jobs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User|null $verifiedByAdmin
 * @method static \Illuminate\Database\Eloquent\Builder|Technician newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Technician newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Technician onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Technician query()
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereAverageRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereServiceCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereTotalJobsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician whereYearsOfExperience($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Technician withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Technician withoutTrashed()
 */
	class Technician extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $technician_id
 * @property string $type
 * @property string $title
 * @property string $file_path
 * @property string $status
 * @property string|null $rejection_reason
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Technician $technician
 * @property-read \App\Models\User|null $verifier
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianDocument whereVerifiedBy($value)
 */
	class TechnicianDocument extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $technician_id
 * @property int $job_id
 * @property string $latitude
 * @property string $longitude
 * @property string|null $heading
 * @property string|null $speed
 * @property \Illuminate\Support\Carbon $located_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Job $job
 * @property-read \App\Models\Technician $technician
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereHeading($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereLocatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereTechnicianId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TechnicianLocation whereUpdatedAt($value)
 */
	class TechnicianLocation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $transaction_number
 * @property int $wallet_id
 * @property string $type
 * @property string $amount
 * @property string $balance_before
 * @property string $balance_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereBalanceBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTransactionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereWalletId($value)
 */
	class Transaction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string $phone
 * @property string $password
 * @property string|null $profile_image
 * @property string $role
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $phone_verified_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Notification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Technician|null $technician
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\Models\Wallet|null $wallet
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfileImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $balance
 * @property string $pending_balance
 * @property string $total_earned
 * @property string $total_withdrawn
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet wherePendingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereTotalEarned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereTotalWithdrawn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUserId($value)
 */
	class Wallet extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $withdrawal_number
 * @property int $user_id
 * @property int|null $processed_by
 * @property string $amount
 * @property string $method
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $processedBy
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal query()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereProcessedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereWithdrawalNumber($value)
 */
	class Withdrawal extends \Eloquent {}
}

