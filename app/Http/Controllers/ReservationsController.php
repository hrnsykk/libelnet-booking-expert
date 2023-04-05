<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\View\View;

class ReservationsController extends Controller
{
    private string $url;
    private string $token;
    private array $rentable_identities = [];
    private array $reservations = [];
    public function __construct()
    {
        $this->url = env("BOOKING_EXPERTS_URL");
        $this->token = env("BOOKING_EXPERTS_TOKEN");
    }

    /**
     * Return View 
     *
     * @return View
     */
    public function index(): View
    {
        $this->getReservations();
        ksort($this->reservations);
        sort($this->rentable_identities);

        return view("welcome", ['reservations' => $this->reservations, 'rentable_identities' => $this->rentable_identities]);
    }

    /**
     * Pull data from Booking Expert Api and apply necessary filters
     *
     * @return void
     */
    public function getReservations(): void
    {
        $start_date = "2021/11/01";
        $end_date = "2021/12/31";

        $response = Http::accept('application/vnd.api+json')->withHeaders(['x-api-key' => $this->token])->withUrlParameters(['start_date' => $start_date,  'end_date' => $end_date])->get($this->url . 'reservations');

        if ($response)
            foreach ($response['data'] as $value) {
                $date = new \DateTime($value['attributes']['start_date']);
                $week = $date->format("W");
                $rentable_identity = $value['relationships']['rentable_identity']['data']['id'];
                $this->reservations[$week][$rentable_identity][] =  $value['id'];
            }

        $this->setRentableIdentities();
    }

    /**
     * Removing Duplicate Rentable Identities from the our query
     *
     * @return void
     */
    public function setRentableIdentities(): void
    {
        foreach ($this->reservations as  $value) {
            if (!in_array(key($value), $this->rentable_identities))
                array_push($this->rentable_identities, key($value));
        }
    }
}
