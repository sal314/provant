<?php

/**
 * This model creates a list of links for the Trackers
 * @author Scott LePage
 * @date	12/13/2010
 * @version 1.1
 */

class UserTrackerModel {

	private $links = null;

	public function __construct () {
		$li = array(array('link'  => '/MyProgress/Index',           'text'  => 'My Progress'),
		            array('link'  => '/TrackerWeight/Index',        'text'  => 'Weight Tracker'),
		            array('link'  => '/TrackerBP/Index',            'text'  => 'Blood Pressure Tracker'),
		            array('link'  => '/TrackerCholesterol/Index',   'text'  => 'Cholesterol Tracker'),
		            array('link'  => '/MealPlan/Create',            'text'  => 'Meal Planner'),
		            array('link'  => '/FoodLog/Index',              'text'  => 'Food Log'),
		            array('link'  => '/PortionPlate/Index',         'text'  => 'Portion Plate'),
		            array('link'  => '/WorkoutPlan/Create',         'text'  => 'Exercise Planner'),
		            array('link'  => '/WorkoutPlan/ExerciseLog',    'text'  => 'Exercise Log'),
		            array('link'  => '/TrackerMeasurements/Index',  'text'  => 'Body Measurements Tracker'),
		            array('link'  => '/TrackerPedometer/Index',     'text'  => 'Pedometer Tracker'),
		            array('link'  => '/TrackerBloodGlucose/Index',  'text'  => 'Blood Glucose Tracker')
		      );

		$this->links = $li;
	}


	public function getLinks() {
		return $this->links;
	}
}
