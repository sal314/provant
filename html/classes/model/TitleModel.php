<?php

	/**
	 *	@abstract - Translates a URL string to a page title
	 *	@author - S.LePage
	 *	@version - 1.1
	 */

class TitleModel {

	//
	// Array of titles (key = redirected URL found in $_SERVER['REDIRECT_URL'])
	//
	private $titles = array("//"                     => "Login",
									"/User/info"                     => "User Profile",
									"/Landing/logout"                => "Logout",
									"/Landing/Login"                 => "Login",
									"/User/Index"                    => "Home",
									"/Register/AddUser"							 => "Register User",
									"/Messages/Index"                => "Message Center",
									"/Messages/Compose"              => "Message Center",
									"/Messages/Sent"                 => "Message Center",
									"/Messages/Archive"              => "Message Center",
									"/Page/Trackers"                 => "Resources and Tools",
									"/MyProgress/Index"              => "My Progress",
									"/TrackerWeight/Index"           => "Weight Tracker",
									"/MealPlan/Index"								 => "Meal Planner",
									"/MealPlan/Create"               => "Create a Meal Plan",
									"/MealPlan/Display"							 => "Display a Meal Plan",
									"/FoodLog/Index"                 => "Food Log",
									"/FoodLog/AddComment"            => "Food Log",
									"/CustomFoods/AddFood"           => "Add Custom Food",
									"/WorkoutPlan/Index"             => "Exercise Planner",
									"/WorkoutPlan/Create"            => "Create an Exercise Plan",
									"/WorkoutPlan/Display"           => "Display an Exercise Plan",
									"/WorkoutPlan/ExerciseLog"       => "Exercise Log",
									"/WorkoutPlan/AddComment"        => "Exercise Log",
									"/WorkoutPlan/AddCustomExercise" => "Add Custom Exercises",
									"/WorkoutPlan/Supplemental"      => "Manage Supplemental Exercises",
									"/TrackerMeasurements/Index"     => "Body Measurements Tracker",
									"/TrackerPedometer/Index"        => "Pedometer Tracker",
									"/TrackerBP/Index"               => "Blood Pressure Tracker",
									"/TrackerCholesterol/Index"      => "Cholesterol Tracker",
									"/TrackerBloodGlucose/Index"     => "Blood Glucose Tracker",
									"/IFocus/Index"                  => "iFOCUS Health Assessment",
									"/IFocus/topic"                  => "iFOCUS Health Assessment",
									"/iFocus/Total"                  => "iFOCUS Health Assessment",
									"/ModuleBreakIT/"                => "Break IT Module",
									"/ModuleBreakIT/week"            => "Break IT Module",
									"/ModuleLoseIT/"                 => "Lose IT Module",
									"/ModuleLoseIT/week"             => "Lose IT Module",
									"/ModuleControlIT/"              => "Control IT Module",
									"/ModuleControlIT/week"          => "Control IT Module",
									"/ModuleMoveIT/"                 => "Move IT Module",
									"/ModuleMoveIT/week"             => "Move IT Module",
									"/ModuleReduceIT/"               => "Reduce IT Module",
									"/ModuleReduceIT/week"           => "Reduce IT Module",
									"/ModuleBreatheIT/"              => "Breathe IT Module",
									"/ModuleBreatheIT/week"          => "Breathe IT Module",
									"/ModuleLiftIT/"                 => "Lift IT Module",
									"/ModuleLiftIT/week"             => "Lift IT Module",
									"/HealthArticles/Index"          => "Health Articles",
									"/HealthArticles/getChunk"       => "Health Articles",
									"/HealthLibrary/Index"           => "Health Library",
									"/HealthLibrary/getChunk"        => "Health Library",
									"/HealthyAchievements/Index"     => "Healthy Achievements",
									"/page/kits"                     => "Screening Kits",
									"/Page/ITModules"                => "myFocus Modules",
									"/HomeHealthScreeningKit/Index"  => "Home Health Screening Kit",
									"/LabVoucherKit/RequestKit"      => "Lab Voucher Kit",
									"/PortionPlate/Index"            => "Portion Plate",
									"/Page/Privacy"                  =>	"Privacy Page");

	//
	// getTitleString
	//	Return the titles based on input url
	//
	public function getTitleString($url) {
		//Divide the URL into parts
		$u = explode("/", $url);

		//Build the URL based on the first two parts
		$url = "/";
		if (isset($u[1])) {
			$url .= $u[1];
		}
		$url .= "/";
		if (isset($u[2])) {
			$url .= $u[2];
		}

		//Create the title string
		$title = "Provant Health Solutions";
		if (isset($this->titles[$url])) {
			$title .= " | " . $this->titles[$url];
		}
		else {
			if ($u[1] == "Register") {				//Special case for user registration
				$title .= " | Register User";
			}
			else if ($u[1] == "admin") {
				$title .= " | Admin";
			}
		}

		return $title;
	}
}

