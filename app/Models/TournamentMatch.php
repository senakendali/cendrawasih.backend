<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'pool_id',
        'round',
        'match_number',
        'participant_1',
        'participant_2',
        'winner_id',
        'next_match_id'
    ];

    // Relasi ke Pool
    public function pool()
    {
        return $this->belongsTo(Pool::class);
    }

    // Relasi ke Peserta 1
    public function participantOne()
    {
        return $this->belongsTo(TeamMember::class, 'participant_1')->with('contingent');
    }

    // Relasi ke Peserta 2
    public function participantTwo()
    {
        return $this->belongsTo(TeamMember::class, 'participant_2')->with('contingent');
    }

    // Relasi ke Pemenang
    public function winner()
    {
        return $this->belongsTo(TeamMember::class, 'winner_id');
    }

    // Relasi ke Pertandingan Selanjutnya
    public function nextMatch()
    {
        return $this->belongsTo(TournamentMatch::class, 'next_match_id');
    }

    
    public function scheduleDetail()
    {
        return $this->hasOne(MatchScheduleDetail::class, 'tournament_match_id');
    }

    public function matchCategory()
    {
        return $this->belongsTo(\App\Models\MatchCategory::class, 'match_category_id');
    }

    // Semua pertandingan yang menunjuk ke match ini sebagai next_match
    public function previousMatches()
    {
        return $this->hasMany(TournamentMatch::class, 'next_match_id');
    }

    // Match yang menunjuk ke pertandingan ini (digunakan untuk label "Pemenang dari Partai #X")
    public function previousMatch()
    {
        return $this->hasOne(TournamentMatch::class, 'next_match_id')->orderBy('match_number');
    }







    

}

