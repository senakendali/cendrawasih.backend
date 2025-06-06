<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryClass extends Model
{
    protected $table = 'category_classes';
    protected $guarded = ['created_at', 'updated_at'];
    protected $dates = ['created_at', 'updated_at'];

    public function ageCategory()
    {
        return $this->belongsTo(AgeCategory::class);
    }

    public function matchClasificationDetails()
    {
        return $this->hasMany(MatchClasificationDetail::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function pools()
    {
        return $this->hasMany(Pool::class);
    }

    public function matchCategory()
    {
        return $this->belongsTo(MatchCategory::class, 'match_category_id');
    }

}
