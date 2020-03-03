<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\UsesCodeAndAuthedExclusive;
use Illuminate\Support\Facades\Auth;

class Property extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, UsesCodeAndAuthedExclusive, SoftDeletes;

    protected $appends = ['is_favorite'];

    public function getIsFavoriteAttribute()
    {
        $customersGuard = Auth::guard('customers');

        if ($customersGuard->check()) {
            return Favorite::where('property_code', $this->code)
            ->where('customer_id', $customersGuard->user()->id)
            ->exists();
        } else {
            return null;
        }
    }

    // /**
    //  * The primary key associated with the table.
    //  *
    //  * @var string
    //  */
    // protected $primaryKey = 'code';

    // /**
    //  * Indicates if the IDs are auto-incrementing.
    //  *
    //  * @var bool
    //  */
    // public $incrementing = false;

    // /**
    //  * The "type" of the auto-incrementing ID.
    //  *
    //  * @var string
    //  */
    // protected $keyType = 'string';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['code'];

    public function property($code)
    {
        return $this->with($this->with)->findOrFail($code);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photo', 'photos', 'video', 'brochure', 'main_title', 'side_title',
        'heading_title', 'description_text', 'country', 'state', 'city', 'suburb',
        'type', 'interior_surface', 'exterior_surface', 'features', 'is_exclusive',
        'price', 'price_lower_range', 'price_upper_range', 'updated_at'
    ];

    // /**
    //  * The attributes excluded from the model's JSON form.
    //  *
    //  * @var array
    //  */
    // protected $hidden = [
    //     'id',
    // ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
