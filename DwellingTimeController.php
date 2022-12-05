<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;
use Kreait\Firebase\Auth;
use GuzzleHttp\Client;
use DB;

class DwellingTimeController extends Controller
{
	
  public function __construct(Database $database, Auth $auth)
  {
      $this->database = $database;
      $this->auth = $auth;
  }

  public function index()
  {
      $tourList = DB::table('tour')
              ->select('tour_id', 'tour.tour_name', 'tour.tour_period_start', 'tour.tour_period_end')
              ->get();
      return view('dwellingtime.dwellingtime', compact('tourList'));
  }

	public function getTourismData($id)
	{
    $existedPlayerIDinFB = $this->database->getReference('tourist')->getChildKeys();
    $getPlayerDetailFromFB = $this->database->getReference('tourist')->getValue();
    $playerData = [];
    for ($i = 0; $i < count($existedPlayerIDinFB); $i++) {
      if ($getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['tourCode'] == $id) {
        $toStoreInFirebase = [
          'age' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['age'],
          'health' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['health'],
          'gender' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['gender'],
          'type' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['type'],
          'exp' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['exp']
        ];
        array_push($playerData, $toStoreInFirebase);
      }
    }
    
    $scheduleData = [];
    $daysInScheduleList = $this->database->getReference('tour_code/' . $id . '/scheduleList/')->getChildKeys();
    for ($k = 0; $k < count($daysInScheduleList); $k++) {
      $key = $this->database->getReference('tour_code/' . $id . '/scheduleList/' . $daysInScheduleList[$k] )->getChildKeys();
      for ($j = 0; $j < count($key); $j++) {
        if ($this->database->getReference('tour_code/' . $id . '/scheduleList/' . $daysInScheduleList[$k] . '/' . $key[$j] . '/tourismSpotCategory')->getSnapshot()->exists()) {
          $scheduleDataFromFB = [
            'tourismSpot' => $this->database->getReference('tour_code/' . $id . '/scheduleList/' . $daysInScheduleList[$k] . '/' . $key[$j] . '/tourismSpotCategory')->getSnapshot()->getValue(),
            'dayNumber' => $daysInScheduleList[$k],
            'date' => $this->database->getReference('tour_code/' . $id . '/scheduleList/' . $daysInScheduleList[$k] . '/' . $key[$j] . '/scheduleDay')->getSnapshot()->getValue(),
            'id' => $key[$j]
          ];
          array_push($scheduleData, $scheduleDataFromFB);
        }
      }
    }
    return view('dwellingtime.detail', compact('playerData', 'id', 'scheduleData'));
  }

    

  public function retrieveData(Request $request)
  {
    $request = array_remove_null($request->all());
    $existedPlayerIDinFB = $this->database->getReference('tourist')->getChildKeys();
    $getPlayerDetailFromFB = $this->database->getReference('tourist')->getValue();
    $playerData = [];
    $dataBody = [];
    for ($y = 0; $y < count($request['weather']); $y++) {
      for ($i = 0; $i < count($existedPlayerIDinFB); $i++) {
        $toStoreInFirebase = [
          'age' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['age'],
          'health' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['health'],
          'gender' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['gender'],
          'type' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['type'],
          'exp' => $getPlayerDetailFromFB[$existedPlayerIDinFB[$i]]['exp'],
          'spot' => $request['tourismSpot'][$y],
          'weather' => $request['weather'][$y],
          'temperature' => $request['temperature'][$y],
          'humidity' => $request['humidity'][$y]
        ];
        array_push($playerData, $toStoreInFirebase);
      }
      $client = new Client();
      $data = $client->post('http://127.0.0.1:5000/dwellpost',
      [
        'json' => [
          'tourist' => $playerData
        ]
      ]);
      $dataBodyRaw = $data->getBody()->getContents();
      $dataBody[$request['id'][$y]] = [
        $request['dayNumber'][$y] . '/' . $request['id'][$y] . '/predictedTime/' => (int) $dataBodyRaw
      ];
    }

    for ($j= 0; $j < count($dataBody); $j++) {
        $this->database->getReference('tour_code/' . $request['idTour'] . '/scheduleList/')->update($dataBody[array_keys($dataBody)[$j]]);
    }
    return redirect()->intended(route('dwellingtime.grab', $request['idTour']));

  }

}
