<?php
  require_once (LIB_ROOT."classes/common/Database.class.php");
  require_once (ROOT_DIR."classes/model/IFocusModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");

/**
 * This is the model code for the Food Log portion of Provant
 * @author Scott LePage
 * @date	7/6/2010
 * @version 1.1
 */

class FoodLogModel {

	private $dbOb = null;
	private $id = 0;

	public function __construct($altId=0) {
		$this->dbOb = Database::create();
		$cred = UserCredentials::load();
		if(!$altId)
			$this->id = $this->dbOb->escape_string($cred->getId());
		else 
			$this->id = $this->dbOb->escape_string($altId);
	}


	public function getMeals() {
		$sql = "SELECT MealID, MealName FROM p_food_meal ORDER BY MealOrder";
		$data = $this->dbOb->query($sql);
		return $data;
	}

	public function getLastEntry() {
		$sql = "SELECT date_entered FROM u_food_log WHERE z_user_id = " . $this->id . " ORDER BY date_entered DESC";
		$last = $this->dbOb->getOne($sql);

		if (!$last) {
			return array();
		}

		$foods = $this->getFoodLog($last);

		$retDate = $last;
		$retCals = 0.0;
		$retCarbs = 0.0;
		$retProt = 0.0;
		$retFats = 0.0;
		foreach ($foods as $food) {
			$retCals += $food['total_calories'];
			$retCarbs += $food['carbohydrates'];
			$retProt += $food['protein'];
			$retFats += $food['fats'];
		}

		return array('date_entered' => $retDate,
		             'calories' => $retCals, 
		             'carbohydrates' => $retCarbs, 
		             'protein' => $retProt,
		             'fats' => $retFats);
	}


	public function getFoodLog($date) {
		/* Return the food eaten by this user for the date specified */
		/* If no date, use today's date */
		$t = isset($date) ? $date : date('Y-m-d');
		try {
			$sql = "SELECT fl.id, fl.custom, fd.FoodID, fd.FoodName, srv.ServingAmountValue, unit.UnitName, nut.NutrientValue, fl.MealID, fl.ServingSize " .
						"FROM u_food_log as fl " .
						"JOIN p_food as fd on fl.FoodID = fd.FoodID " .
						"JOIN p_food_serving_types as srv on fl.FoodID = srv.FoodID " .
						"JOIN p_food_nutrients as nut on fl.FoodID = nut.FoodID " .
						"JOIN p_food_unit as unit on unit.UnitID = srv.ServingAmountUnitID " .
						"WHERE fl.z_user_id = " . $this->id .
						" AND fl.date_entered LIKE '%" . $this->dbOb->escape_string($t) . "%'" .
						" AND fl.ServingTypeID = srv.ServingTypeID" .
						" AND fl.UnitID = unit.UnitID" .
						" AND fl.is_active = 1" .
						" AND nut.NutrientID = 1" .
						" AND fl.custom = 0";
			$data1 = $this->dbOb->query($sql);

			if ($data1) {
				foreach ($data1 as &$datum) {
					// Get carbs, protein and fats
					$sql = "SELECT NutrientID, NutrientValue " .
									"FROM p_food_nutrients " .
									"WHERE FoodID = " . $datum['FoodID'] . " " .
									"AND NutrientID IN (2, 3, 4)";
					$nuts = $this->dbOb->query($sql);

					if ($datum['ServingSize'] > 0.0) {
						$datum['ratio'] = $datum['ServingSize'] / $datum['ServingAmountValue'];
						$datum['serving_size'] = $datum['ServingSize'];
					}
					else {
						$datum['ratio'] = 1.0;
						$datum['serving_size'] = $datum['ServingAmountValue'];
					}
					$datum['total_calories'] = round(($datum['ratio'] * $datum['NutrientValue']), 1);

					$datum['carbohydrates'] = 0;
					$datum['protein'] = 0;
					$datum['fats'] = 0;

					foreach ($nuts as $nut) {
						if ($nut['NutrientID'] == 2) {
							$datum['protein'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 3) {
							$datum['carbohydrates'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 4) {
							$datum['fats'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					}
				}
			}
		}
		catch (Exception $e) {
			throw($e);
		}

		try {
			$sql = "SELECT fl.id, fl.custom, fd.FoodID, fd.FoodName, srv.ServingAmountValue, unit.UnitName, nut.NutrientValue, fl.MealID, fl.ServingSize " .
						"FROM u_food_log as fl " .
						"JOIN u_custom_foods as fd on fl.FoodID = fd.FoodID " .
						"JOIN u_custom_foods_serving_types as srv on fl.FoodID = srv.FoodID " .
						"JOIN u_custom_foods_nutrients as nut on fl.FoodID = nut.FoodID " .
						"JOIN p_food_unit as unit on unit.UnitID = srv.ServingAmountUnitID " .
						"WHERE fl.z_user_id = " . $this->id .
						" AND fl.date_entered LIKE '%" . $this->dbOb->escape_string($t) . "%'" .
						" AND fl.UnitID = unit.UnitID" .
						" AND fl.is_active = 1" .
						" AND nut.NutrientID = 1" .
						" AND fl.custom = 1";
			$data2 = $this->dbOb->query($sql);
			if ($data2) {
				foreach ($data2 as &$datum) {

					$sql = "SELECT NutrientID, NutrientValue " .
									"FROM u_custom_foods_nutrients " .
									"WHERE FoodID = " . $datum['FoodID'] . " " .
									"AND NutrientID IN (2, 3, 4)";
					$nuts = $this->dbOb->query($sql);

					if ($datum['ServingSize'] > 0.0) {
						$datum['ratio'] = $datum['ServingSize'] / $datum['ServingAmountValue'];
						$datum['serving_size'] = $datum['ServingSize'];
					}
					else {
						$datum['ratio'] = 1.0;
						$datum['serving_size'] = $datum['ServingAmountValue'];
					}
					$datum['total_calories'] = round(($datum['NutrientValue'] * $datum['ratio']), 1);

					$datum['carbohydrates'] = 0;
					$datum['protein'] = 0;
					$datum['fats'] = 0;

					foreach ($nuts as $nut) {
						if ($nut['NutrientID'] == 2) {
							$datum['protein'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 3) {
							$datum['carbohydrates'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 4) {
							$datum['fats'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					}
				}
			}
		}
		catch (Exception $e) {
			throw($e);
		}
		$data = null;
		if ($data1) {
			if ($data2) {
				$data = array_merge($data1, $data2);
			}
			else {
				$data = $data1;
			}
		}
		else {
			if ($data2) {
				$data = $data2;
			}
		}
		return $data;
	}

	
	public function getTotalFoodLog($date) {
		/* Return the food eaten by this user for the date specified */
		/* If no date, use today's date */
		$t = isset($date) ? $date : date('Y-m-d');
		try {
			$sql = "SELECT fl.id, fd.FoodID, fd.FoodName, srv.ServingAmountValue, unit.UnitName, nut.NutrientValue, fl.MealID, fl.ServingSize " .
						"FROM u_food_log as fl " .
						"JOIN p_food as fd on fl.FoodID = fd.FoodID " .
						"JOIN p_food_serving_types as srv on fl.FoodID = srv.FoodID " .
						"JOIN p_food_nutrients as nut on fl.FoodID = nut.FoodID " .
						"JOIN p_food_unit as unit on unit.UnitID = srv.ServingAmountUnitID " .
						"WHERE fl.z_user_id = " . $this->id .
						" AND fl.date_entered LIKE '%" . $this->dbOb->escape_string($t) . "%'" .
						" AND fl.ServingTypeID = srv.ServingTypeID" .
						" AND fl.UnitID = unit.UnitID" .
						" AND fl.is_active = 1" .
						" AND nut.NutrientID = 1" .
						" AND fl.custom = 0";
			$data1 = $this->dbOb->query($sql);

			if ($data1) {
				foreach ($data1 as &$datum) {
					// Get carbs, protein and fats
					$sql = "SELECT NutrientID, NutrientValue " .
									"FROM p_food_nutrients " .
									"WHERE FoodID = " . $datum['FoodID'] . " " ;
									
					$nuts = $this->dbOb->query($sql);

					if ($datum['ServingSize'] > 0.0) {
						$datum['ratio'] = $datum['ServingSize'] / $datum['ServingAmountValue'];
						$datum['serving_size'] = $datum['ServingSize'];
					}
					else {
						$datum['ratio'] = 1.0;
						$datum['serving_size'] = $datum['ServingAmountValue'];
					}
					$datum['total_calories'] = round(($datum['ratio'] * $datum['NutrientValue']), 1);

					$datum['carbohydrates'] = 0;
					$datum['protein'] = 0;
					$datum['fats'] = 0;
					$datum['alcohol'] = 0;
					$datum['cholesterol'] = 0;
					$datum['saturated fat'] = 0;
					$datum['mono fat'] = 0;
					$datum['poly fat'] = 0;
					$datum['Oleic'] = 0;
					$datum['Linoleic'] = 0;
					$datum['EPA'] = 0;
					$datum['DHA'] = 0;
					$datum['Trans Fat'] = 0;
					$datum['Sodium'] = 0;
					$datum['Potassium'] = 0;
					$datum['Vitamin ARE'] = 0;
					$datum['Vit AIU'] = 0;
					$datum['Beta-C'] = 0;
					$datum['Alpha-C'] = 0;
					$datum['Lutein'] = 0;
					$datum['Beta-Crypto'] = 0;
					$datum['Lycopene'] = 0;
					$datum['Vitamin C'] = 0;
					$datum['Calcium'] = 0;
					$datum['Iron'] = 0;
					$datum['Vit Dug'] = 0;
					$datum['Vit DIU'] = 0;
					$datum['Vit EIU'] = 0;
					$datum['Vit Emg'] = 0;
					$datum['Alpha-T'] = 0;
					$datum['Thiamin'] = 0;
					$datum['Riboflavin'] = 0;
					$datum['Niacin'] = 0;
					$datum['Vit B6'] = 0;
					$datum['Folate'] = 0;
					$datum['Vit B12'] = 0;
					$datum['Biotin'] = 0;
					$datum['Panto Acid'] = 0;
					$datum['Vit K'] = 0;
					$datum['Phosphorus'] = 0;
					$datum['Iodine'] = 0;
					$datum['Magnesium'] = 0;
					$datum['Zinc'] = 0;
					$datum['Copper'] = 0;
					$datum['Manganese'] = 0;
					$datum['Selenium'] = 0;
					$datum['Fluoride'] = 0;
					$datum['Chromium'] = 0;
					$datum['Molybdenum'] = 0;
					$datum['Diet Fiber'] = 0;
					$datum['Sol Fiber'] = 0;
					$datum['Insol Fiber'] = 0;
					$datum['Crude Fiber'] = 0;
					$datum['Sugar'] = 0;
					$datum['Glucose'] = 0;
					$datum['Galactose'] = 0;
					$datum['Fructose'] = 0;
					$datum['Sucrose'] = 0;
					$datum['Lactose'] = 0;
					$datum['Maltose'] = 0;
					$datum['Tryptophan'] = 0;
					$datum['Threonine'] = 0;
					$datum['Isoleucine'] = 0;
					$datum['Leucine'] = 0;
					$datum['Lysine'] = 0;
					$datum['Methionine'] = 0;
					$datum['Cystine'] = 0;
					$datum['Phenylalanine'] = 0; 
					$datum['Tyrosine'] = 0;
					$datum['Valine'] = 0;
					$datum['Arginine'] = 0;
					$datum['Histidine'] = 0;
					$datum['Alanine'] = 0;
					$datum['Asp Acid'] = 0;
					$datum['Glut Acid'] = 0;
					$datum['Glycine'] = 0;
					$datum['Proline'] = 0;
					$datum['Serine'] = 0;
					$datum['Moisture'] = 0;
					$datum['Ash'] = 0;
					$datum['Caffeine'] = 0; 
					$datum['Fat Cal'] = 0;
					$datum['Sat Fat Cal'] = 0; 
					$datum['Sugar Alcohol'] = 0;
					$datum['Other Carbohydrate'] = 0; 
					$datum['Folate'] = 0;
					$datum['Vit A RAE'] = 0; 
					
					foreach ($nuts as $nut) {
						if ($nut['NutrientID'] == 2) {
							$datum['protein'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 3) {
							$datum['carbohydrates'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 4) {
							$datum['fats'] = $nut['NutrientValue'] * $datum['ratio'];
						} 
						else  if ($nut['NutrientID'] == 5) {
							
							$datum['alcohol'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 6) {
							
							$datum['cholesterol'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 7) {
							
							$datum['saturated fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 8) {
							
							$datum['mono fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 9) {
							
							$datum['poly fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 23) {
							
							$datum['Oleic'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 26) {
							
							$datum['Linoleic'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 27) {
							
							$datum['Linolenic'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 30) {
							
							$datum['EPA'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 32) {
							
							$datum['DHA'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 33) {
							
							$datum['Trans Fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 34) {
							
							$datum['Sodium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 35) {
							
							$datum['Potassium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 37) {
							
							$datum['Vitamin ARE'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 38) {
							
							$datum['Vit AIU'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 40) {
							
							$datum['Beta-C'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 41) {
							
							$datum['Alpha-C'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 42) {
							
							$datum['Lutein'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 43) {
							
							$datum['Beta-Crypto'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 44) {
							
							$datum['Lycopene'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 45) {
							
							$datum['Vitamin C'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 46) {
							
							$datum['Calcium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 47) {
							
							$datum['Iron'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 48) {
							
							$datum['Vit Dug'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 49) {
							
							$datum['Vit DIU'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 50) {
							
							$datum['Vit Emg'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 51) {
							
							$datum['Vit EIU'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 52) {
							
							$datum['Alpha-T'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 53) {
							
							$datum['Thiamin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 54) {
							
							$datum['Riboflavin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 55) {
							
							$datum['Niacin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 57) {
							
							$datum['Vit B6'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 58) {
							
							$datum['Folate'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 59) {
							
							$datum['Vit B12'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 60) {
							
							$datum['Biotin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 61) {
							
							$datum['Panto Acid'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 62) {
							
							$datum['Vit K'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 63) {
							
							$datum['Phosphorus'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 64) {
							
							$datum['Iodine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 65) {
							
							$datum['Magnesium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 66) {
							
							$datum['Zinc'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 67) {
							
							$datum['Copper'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 68) {
							
							$datum['Manganese'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 69) {
							
							$datum['Selenium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 70) {
							
							$datum['Fluoride'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 71) {
							
							$datum['Chromium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 72) {
							
							$datum['Molybdenum'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 74) {
							
							$datum['Diet Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 75) {
							
							$datum['Sol Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 76) {
							
							$datum['Insol Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 77) {
							
							$datum['Crude Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 78) {
							
							$datum['Sugar'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					
						else  if ($nut['NutrientID'] == 79) {
							
							$datum['Glucose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 80) {
							
							$datum['Galactose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 81) {
							
							$datum['Fructose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 82) {
							
							$datum['Sucrose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 83) {
							
							$datum['Lactose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 84) {
							
							$datum['Maltose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 85) {
							
							$datum['Tryptophan'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 86) {
							
							$datum['Threonine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 87) {
							
							$datum['Isoleucine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 88) {
							
							$datum['Leucine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 89) {
							
							$datum['Lysine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 90) {
							
							$datum['Methionine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 91) {
							
							$datum['Cystine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 92) {
							
							$datum['Phenylalanine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 93) {
							
							$datum['Tyrosine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 94) {
							
							$datum['Valine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 95) {
							
							$datum['Arginine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 96) {
							
							$datum['Histidine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 97) {
							
							$datum['Alanine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 98) {
							
							$datum['Asp Acid'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					
						else  if ($nut['NutrientID'] == 99) {
							
							$datum['Glut Acid'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 100) {
							
							$datum['Glycine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					
						else  if ($nut['NutrientID'] == 101) {
							
							$datum['Proline'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 102) {
							
							$datum['Serine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 105) {
							
							$datum['Moisture'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 106) {
							
							$datum['Ash'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 107) {
							
							$datum['Caffeine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 109) {
							
							$datum['Fat Cal'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 110) {
							
							$datum['Sat Fat Cal'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 111) {
							
							$datum['Sugar Alcohol'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 112) {
							
							$datum['Other Carbohydrate'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 113) {
							
							$datum['Folate'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 114) {
							
							$datum['Vit ARAE'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					}
				}
			}
		}
		catch (Exception $e) {
			throw($e);
		}

		try {
			$sql = "SELECT fl.id, fd.FoodID, fd.FoodName, srv.ServingAmountValue, unit.UnitName, nut.NutrientValue, fl.MealID, fl.ServingSize " .
						"FROM u_food_log as fl " .
						"JOIN u_custom_foods as fd on fl.FoodID = fd.FoodID " .
						"JOIN u_custom_foods_serving_types as srv on fl.FoodID = srv.FoodID " .
						"JOIN u_custom_foods_nutrients as nut on fl.FoodID = nut.FoodID " .
						"JOIN p_food_unit as unit on unit.UnitID = srv.ServingAmountUnitID " .
						"WHERE fl.z_user_id = " . $this->id .
						" AND fl.date_entered LIKE '%" . $this->dbOb->escape_string($t) . "%'" .
						" AND fl.UnitID = unit.UnitID" .
						" AND fl.is_active = 1" .
						" AND nut.NutrientID = 1" .
						" AND fl.custom = 1";
			$data2 = $this->dbOb->query($sql);
			if ($data2) {
				foreach ($data2 as &$datum) {

					$sql = "SELECT NutrientID, NutrientValue " .
									"FROM u_custom_foods_nutrients " .
									"WHERE FoodID = " . $datum['FoodID'];
									
					$nuts = $this->dbOb->query($sql);

					if ($datum['ServingSize'] > 0.0) {
						$datum['ratio'] = $datum['ServingSize'] / $datum['ServingAmountValue'];
						$datum['serving_size'] = $datum['ServingSize'];
					}
					else {
						$datum['ratio'] = 1.0;
						$datum['serving_size'] = $datum['ServingAmountValue'];
					}
					$datum['total_calories'] = round(($datum['NutrientValue'] * $datum['ratio']), 1);

					$datum['carbohydrates'] = 0;
					$datum['protein'] = 0;
					$datum['fats'] = 0;
					$datum['alcohol'] = 0;
					$datum['cholesterol'] = 0;
					$datum['saturated fat'] = 0;
					$datum['mono fat'] = 0;
					$datum['poly fat'] = 0;
					$datum['Oleic'] = 0;
					$datum['Linoleic'] = 0;
					$datum['EPA'] = 0;
					$datum['DHA'] = 0;
					$datum['Trans Fat'] = 0;
					$datum['Sodium'] = 0;
					$datum['Potassium'] = 0;
					$datum['Vitamin ARE'] = 0;
					$datum['Vit AIU'] = 0;
					$datum['Vit EIU'] = 0;
					$datum['Vit Emg'] = 0;
					$datum['Beta-C'] = 0;
					$datum['Alpha-C'] = 0;
					$datum['Lutein'] = 0;
					$datum['Beta-Crypto'] = 0;
					$datum['Lycopene'] = 0;
					$datum['Vitamin C'] = 0;
					$datum['Calcium'] = 0;
					$datum['Iron'] = 0;
					$datum['Vit Dug'] = 0;
					$datum['Vit DIU'] = 0;
					$datum['Alpha-T'] = 0;
					$datum['Thiamin'] = 0;
					$datum['Riboflavin'] = 0;
					$datum['Niacin'] = 0;
					$datum['Vit B6'] = 0;
					$datum['Folate'] = 0;
					$datum['Vit B12'] = 0;
					$datum['Biotin'] = 0;
					$datum['Panto Acid'] = 0;
					$datum['Vit K'] = 0;
					$datum['Phosphorus'] = 0;
					$datum['Iodine'] = 0;
					$datum['Magnesium'] = 0;
					$datum['Zinc'] = 0;
					$datum['Copper'] = 0;
					$datum['Manganese'] = 0;
					$datum['Selenium'] = 0;
					$datum['Fluoride'] = 0;
					$datum['Chromium'] = 0;
					$datum['Molybdenum'] = 0;
					$datum['Diet Fiber'] = 0;
					$datum['Sol Fiber'] = 0;
					$datum['Insol Fiber'] = 0;
					$datum['Crude Fiber'] = 0;
					$datum['Sugar'] = 0;
					$datum['Glucose'] = 0;
					$datum['Galactose'] = 0;
					$datum['Fructose'] = 0;
					$datum['Sucrose'] = 0;
					$datum['Lactose'] = 0;
					$datum['Maltose'] = 0;
					$datum['Tryptophan'] = 0;
					$datum['Isoleucine'] = 0;
					$datum['Leucine'] = 0;
					$datum['Lysine'] = 0;
					$datum['Methionine'] = 0;
					$datum['Cystine'] = 0;
					$datum['Phenylalanine'] = 0; 
					$datum['Tyrosine'] = 0;
					$datum['Valine'] = 0;
					$datum['Arginine'] = 0;
					$datum['Histidine'] = 0;
					$datum['Alanine'] = 0;
					$datum['Asp Acid'] = 0;
					$datum['Glut Acid'] = 0;
					$datum['Glycine'] = 0;
					$datum['Proline'] = 0;
					$datum['Serine'] = 0;
					$datum['Moisture'] = 0;
					$datum['Ash'] = 0;
					$datum['Caffeine'] = 0; 
					$datum['Fat Cal'] = 0;
					$datum['Sat Fat Cal'] = 0; 
					$datum['Sugar Alcohol'] = 0;
					$datum['Other Carbohydrate'] = 0; 
					$datum['Folate'] = 0;
					$datum['Vit ARAE'] = 0; 
					
					foreach ($nuts as $nut) {
						if ($nut['NutrientID'] == 2) {
							$datum['protein'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 3) {
							$datum['carbohydrates'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else if ($nut['NutrientID'] == 4) {
							$datum['fats'] = $nut['NutrientValue'] * $datum['ratio'];
						} 
						else  if ($nut['NutrientID'] == 5) {
							
							$datum['alcohol'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 6) {
							
							$datum['cholesterol'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 7) {
							
							$datum['saturated fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 8) {
							
							$datum['mono fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 9) {
							
							$datum['poly fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 23) {
							
							$datum['Oleic'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 26) {
							
							$datum['Linoleic'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 27) {
							
							$datum['Linolenic'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 30) {
							
							$datum['EPA'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 32) {
							
							$datum['DHA'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 33) {
							
							$datum['Trans Fat'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 34) {
							
							$datum['Sodium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 35) {
							
							$datum['Potassium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 37) {
							
							$datum['Vitamin ARE'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 38) {
							
							$datum['Vit AIU'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 40) {
							
							$datum['Beta-C'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 41) {
							
							$datum['Alpha-C'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 42) {
							
							$datum['Lutein'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 43) {
							
							$datum['Beta-Crypto'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 44) {
							
							$datum['Lycopene'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 45) {
							
							$datum['Vitamin C'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 46) {
							
							$datum['Calcium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 47) {
							
							$datum['Iron'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 48) {
							
							$datum['Vit Dug'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 49) {
							
							$datum['Vit DIU'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 50) {
							
							$datum['Vit Emg'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 51) {
							
							$datum['Vit EIU'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 52) {
							
							$datum['Alpha-T'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 53) {
							
							$datum['Thiamin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 54) {
							
							$datum['Riboflavin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 55) {
							
							$datum['Niacin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 57) {
							
							$datum['Vit B6'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 58) {
							
							$datum['Folate'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 59) {
							
							$datum['Vit B12'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 60) {
							
							$datum['Biotin'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 61) {
							
							$datum['Panto Acid'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 62) {
							
							$datum['Vit K'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 63) {
							
							$datum['Phosphorus'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 64) {
							
							$datum['Iodine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 65) {
							
							$datum['Magnesium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 66) {
							
							$datum['Zinc'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 67) {
							
							$datum['Copper'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 68) {
							
							$datum['Manganese'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 69) {
							
							$datum['Selenium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 70) {
							
							$datum['Fluoride'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 71) {
							
							$datum['Chromium'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 72) {
							
							$datum['Molybdenum'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 74) {
							
							$datum['Diet Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 75) {
							
							$datum['Sol Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 76) {
							
							$datum['Insol Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 77) {
							
							$datum['Crude Fiber'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 78) {
							
							$datum['Sugar'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					
						else  if ($nut['NutrientID'] == 79) {
							
							$datum['Glucose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 80) {
							
							$datum['Galactose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						else  if ($nut['NutrientID'] == 81) {
							
							$datum['Fructose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 82) {
							
							$datum['Sucrose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 83) {
							
							$datum['Lactose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 84) {
							
							$datum['Maltose'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 85) {
							
							$datum['Tryptophan'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 86) {
							
							$datum['Threonine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 87) {
							
							$datum['Isoleucine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 88) {
							
							$datum['Leucine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 89) {
							
							$datum['Lysine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 90) {
							
							$datum['Methionine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 91) {
							
							$datum['Cystine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 92) {
							
							$datum['Phenylalanine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 93) {
							
							$datum['Tyrosine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 94) {
							
							$datum['Valine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 95) {
							
							$datum['Arginine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 96) {
							
							$datum['Histidine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 97) {
							
							$datum['Alanine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 98) {
							
							$datum['Asp Acid'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					
						else  if ($nut['NutrientID'] == 99) {
							
							$datum['Glut Acid'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 100) {
							
							$datum['Glycine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					
						else  if ($nut['NutrientID'] == 101) {
							
							$datum['Proline'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 102) {
							
							$datum['Serine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 105) {
							
							$datum['Moisture'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 106) {
							
							$datum['Ash'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 107) {
							
							$datum['Caffeine'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 109) {
							
							$datum['Fat Cal'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 110) {
							
							$datum['Sat Fat Cal'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 111) {
							
							$datum['Sugar Alcohol'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 112) {
							
							$datum['Other Carbohydrate'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 113) {
							
							$datum['Folate'] = $nut['NutrientValue'] * $datum['ratio'];
						}
						
						else  if ($nut['NutrientID'] == 114) {
							
							$datum['Vit ARAE'] = $nut['NutrientValue'] * $datum['ratio'];
						}
					}
				}
			}
		}
		catch (Exception $e) {
			throw($e);
		}
		$data = null;
		if ($data1) {
			if ($data2) {
				$data = array_merge($data1, $data2);
			}
			else {
				$data = $data1;
			}
		}
		else {
			if ($data2) {
				$data = $data2;
			}
		}
		return $data;
	}


	public function getNutritionData($flid) {
		$sql = "SELECT * FROM u_food_log WHERE id = " . $this->dbOb->escape_string($flid);
		$row = $this->dbOb->getRow($sql);

		if ($row['custom'] == 0) {
			$sql = "SELECT ServingAmountValue FROM p_food_serving_types " .
							"WHERE FoodID = " . $row['FoodID'] . " " .
							"AND ServingTypeID = " . $row['ServingTypeID'];
			$srvAmt = $this->dbOb->getOne($sql);

			$sql = "SELECT nut.NutrientID, nut.NutrientName, nuts.NutrientValue, u.UnitAbbreviation " .
							"FROM p_food AS fd " .
							"JOIN p_food_nutrients AS nuts ON fd.FoodID = nuts.FoodID " .
							"JOIN p_food_nutrient AS nut ON nuts.NutrientID = nut.NutrientID " .
							"JOIN p_food_unit AS u ON u.UnitID = nut.UnitID " .
							"JOIN p_food_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"WHERE fd.FoodID = " . $row['FoodID'] . " " .
							"AND srv.ServingTypeID = " . $row['ServingTypeID'];
		}
		else {
			$sql = "SELECT ServingAmountValue FROM u_custom_foods_serving_types " .
							"WHERE FoodID = " . $row['FoodID'];
			$srvAmt = $this->dbOb->getOne($sql);

			$sql = "SELECT nut.NutrientID, nut.NutrientName, nuts.NutrientValue, srv.ServingAmountValue, u.UnitAbbreviation " .
							"FROM u_custom_foods AS fd " .
							"JOIN u_custom_foods_nutrients AS nuts ON fd.FoodID = nuts.FoodID " .
							"JOIN p_food_nutrient AS nut ON nuts.NutrientID = nut.NutrientID " .
							"JOIN p_food_unit AS u ON u.UnitID = nut.UnitID " .
							"JOIN u_custom_foods_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"WHERE fd.FoodID = " . $row['FoodID'];
		}
		$data = $this->dbOb->query($sql);
		$ratio = 1.0;
		if ($row['ServingSize'] > 0.0) {
			$ratio = $row['ServingSize'] / $srvAmt;
		}
		$retData = array();
		$disp = array();
		$raw = array();
		foreach($data as $row) {
			$disp[$row['NutrientID']] = $row['NutrientName'] . "&nbsp;&nbsp;&nbsp; " . ($row['NutrientValue'] * $ratio) . $row['UnitAbbreviation'];
			$raw[$row['NutrientID']] = $row['NutrientValue'] * $ratio;
		}
		$retData['display'] = $disp;
		$retData['raw'] = $raw;

		return $retData;
		
		
	}
	
	/**
	 * Nutritional Data as %of daily needs specific to every foodID
	 *
	 * @param int $flid(FoodID)
	 * @return data array for all nutritions
	 */
	
	public function getNutritionDatameal($flid) {
		

			$sql = "SELECT ServingAmountValue FROM p_food_serving_types " .
							"WHERE FoodID = " . $this->dbOb->escape_string($flid) . " " .
						"AND ServingTypeID = 1" ;
			$srvAmt = $this->dbOb->getOne($sql);

			$sql = "SELECT nut.NutrientID, nut.NutrientName, nuts.NutrientValue, u.UnitAbbreviation " .
							"FROM p_food AS fd " .
							"JOIN p_food_nutrients AS nuts ON fd.FoodID = nuts.FoodID " .
							"JOIN p_food_nutrient AS nut ON nuts.NutrientID = nut.NutrientID " .
							"JOIN p_food_unit AS u ON u.UnitID = nut.UnitID " .
							"JOIN p_food_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"WHERE fd.FoodID = " .$this->dbOb->escape_string($flid). " " .
							"AND srv.ServingTypeID = 1" ;
	
		$data = $this->dbOb->query($sql);
		$ratio = 1.0;
	
		
		$retData = array();
		$disp = array();
		$raw = array();
		foreach($data as $row) {
			$disp[$row['NutrientID']] = $row['NutrientName'] . "&nbsp;&nbsp;&nbsp; " . ($row['NutrientValue'] * $ratio) . $row['UnitAbbreviation'];
			$raw[$row['NutrientID']] = $row['NutrientValue'] * $ratio;
		}
		$retData['display'] = $disp;
		$retData['raw'] = $raw;

		return $retData;
		
		
	}


	public function getFoodInfo($flid) {
		$sql = "SELECT * FROM u_food_log WHERE id = " . $this->dbOb->escape_string($flid);
		$row = $this->dbOb->getRow($sql);

		if ($row['custom'] == 0) {
			$sql = "SELECT fd.FoodName, srv.ServingAmountValue, u.UnitName " .
							"FROM p_food AS fd " .
							"JOIN p_food_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"JOIN p_food_unit AS u ON srv.ServingAmountUnitID = u.UnitID " .
							"WHERE srv.ServingTypeID = " . $row['ServingTypeID'] . " " .
							"AND fd.FoodID = " . $row['FoodID'];
		}
		else {
			$sql = "SELECT fd.FoodName, srv.ServingAmountValue, u.UnitName " .
							"FROM u_custom_foods AS fd " .
							"JOIN u_custom_foods_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"JOIN p_food_unit AS u ON srv.ServingAmountUnitID = u.UnitID " .
							"AND fd.FoodID = " . $row['FoodID'];
		}

		$data = $this->dbOb->getRow($sql);
		$food = array('name' => $data['FoodName'],
									'serving_amount' => $data['ServingAmountValue'],
									'serving_unit' => $data['UnitName']);
		if ($row['ServingSize'] > 0) {
			$food['serving_amount'] = $row['ServingSize'];
		}

		return $food;
	}
	
	/**
	 * Meal Planner Food info from p_food table for every food ID	
	 *
	 * @param $flid int
	 * @return food array 
	 */
	
	public function getFoodInfomeal($flid) {
		

		
			$sql = "SELECT fd.FoodName, srv.ServingAmountValue, u.UnitName " .
							"FROM p_food AS fd " .
							"JOIN p_food_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"JOIN p_food_unit AS u ON srv.ServingAmountUnitID = u.UnitID " .
							"WHERE srv.ServingTypeID = 1 "   .
							"AND fd.FoodID = " . $this->dbOb->escape_string($flid);
		

		$data = $this->dbOb->getRow($sql);
		$food = array('name' => $data['FoodName'],
									'serving_amount' => $data['ServingAmountValue'],
									'serving_unit' => $data['UnitName']);


		return $food;
	}



	public function getDailyRecommendedIntake() {
		$dri = array();

		$sql = "SELECT * FROM u_profile WHERE z_user_id = " . $this->id;
		$profile = $this->dbOb->getRow($sql);

		$age = (date('Y', time() - strtotime($profile['dob']))) - 1970;
		$ageflag = "Y";
		if ($age == 0) {
			$age = (date('j', time() - strtotime($profile['dob'])));
			$ageflag = "M";
		}

		$wt = new UserTrackWeightModel($this->id);
		$lb = $wt->getLastEntry();
		if (!$lb) {
			$weight = $profile['inital_weight'];
		}
		else {
			$weight = $lb['weight'];
		}

		$height = ($profile['height_ft'] * 12) + $profile['height_in'];

		if ($ageflag == "M") {
			$mage = $age;
			$age = $age / 12.0;
		}

		if ($profile['gender'] == 'M') {
			$BMR = 66 + (6.23 * $weight) + (12.7 * $height) - (6.8 * $age);
		}
		else if ($profile['gender'] == 'F') {
			$BMR = 655 + (4.35 * $weight) + (4.7 * $height) - (4.7 * $age);
		}
		else {
			$BMR = 0;
		}
		if ($ageflag == "M") {
			$age = $mage;
		}

		$if = new IFocusModel($this->id, false);
		$multiplier = $if->getLifeStyleMultiplier($this->id);
		$calories = $BMR * $multiplier;
		switch($profile['goal']) {
			case "lose_2pw" :
				$target_calories = $calories - 1000;
				break;
			case "lose_1pw" :
				$target_calories = $calories - 500;
				break;
			case "gain_2pw" :
				$target_calories = $calories + 1000;
				break;
			case "gain_1pw" :
				$target_calories = $calories + 500;
				break;
			default :
				$target_calories = $calories;
		}

		$ratio = $target_calories / $calories;
		
		$dri['age'] = $age;
		$dri['ageflag'] = $ageflag;
		$dri['weight'] = $weight;
		$dri['height'] = $height;
		$dri['BMR'] = $BMR;
		$dri['calories'] = $calories;
		$dri['target'] = $target_calories;
		$dri['ratio'] = $ratio;
		$dri['gender'] = $profile['gender'];
		$dri['pregnancy'] = $profile['pregnancy'];
		$dri['lactating'] = $profile['lactating'];

		$ret = $this->getDietaryNeeds($dri);

		return $ret;
	}


	private function getDietaryNeeds($dri) {

		// This array translates the table names from p_food_dietary_needs and
		// the nutrition database index for these nutrients (see table p_food_nutrient)
		// Note that an index of 0 indicates that there is no translation.
		$trans = array (				'carbohydrates'           => 3,
										'fiber'                   => 74,
										'fat'                     => 4,
										'n6_polyunsaturated_fat'  => 26,
										'n3_polyunsaturated_fat'  => 27,
										'saturated_fat'           => 7,
										'protein'                 => 2,
										'histidine'               => 96,
										'isoleucine'              => 87,
										'leucine'                 => 88,
                   						'lysine'                  => 89,
										'methionine_cysteine'     => 90,
										'phenylalanine_tyrosine'  => 92,
										'threonine'               => 86,
										'tryptophan'              => 85,
										'valine'                  => 94,
										'vitamin_a'               => 38,
										'vitamin_c'               => 45,
										'vitamin_d'               => 49,
										'vitamin_e'               => 51,
										'vitamin_k'               => 62,
										'thiamin'                 => 53,
										'riboflavin'              => 54,
										'niacin'                  => 55,
										'vitamin_b6'              => 57,
										'folate'                  => 58,
										'vitamin_b12'             => 59,
										'pantothenic_acid'        => 61,
										'biotin'                  => 60,
										'choline'                 => 0,
										'calcium'                 => 46,
										'chromium'                => 71,
										'copper'                  => 67,
										'fluoride'                => 70,
										'iodine'                  => 64,
										'iron'                    => 47,
										'magnesium'               => 65,
										'manganese'               => 68,
										'molybdenum'              => 72,
										'phosphorus'              => 63,
										'selenium'                => 69,
										'zinc'                    => 66,
										'potassium'               => 35,
										'sodium'                  => 34,
										'chloride'                => 0);


		$age = $dri['age'];
		if ($dri['ageflag'] == "M") {
			if ($age <= 6) {
				$index = 1;
			}
			else {
				$index = 2;
			}
		}
		else {
			if ($age <= 3) {
				$index = 3;
			}
			else if ($age <= 8) {
				$index = 4;
			}
			else if ($age <= 13) {
				$index = 5;
			}
			else if ($age <= 18) {
				$index = 6;
			}
			else if ($age <= 30) {
				$index = 7;
			}
			else if ($age <= 50) {
				$index = 8;
			}
			else if ($age <= 70) {
				$index = 9;
			}
			else {
				$index = 10;
			}
		}

		//Get the dietary needs for this user
		$sql = "SELECT * FROM p_food_dietary_needs " .
						"WHERE ageIdx = " . $index . " " .
						"AND gender = '" . $dri['gender'] . "' " .
						"AND pregnancy = '" . $dri['pregnancy'] . "' " .
						"AND lactating = '" . $dri['lactating'] . "'";
		$diet = $this->dbOb->getRow($sql);

		//Get a list database nutrition identifiers
		$sql = "SELECT NutrientID FROM p_food_nutrient ORDER BY NutrientID";
		$nuts = $this->dbOb->query($sql);

		//Zero the nutrient array
		foreach($nuts as $n) {
			$retArray[$n['NutrientID']] = 0;
		}

		//Populate the ones we have
		foreach($diet as $k => $v) {
			if ($k == "ageIdx") {
				continue;
			}
			else if ($k == "gender") {
				continue;
			}
			else if ($k == "pregnancy") {
				continue;
			}
			else if ($k == "lactating") {
				continue;
			}
			else {
				if ($trans[$k] != 0) {
					$retArray[$trans[$k]] = $v;
				}
			}
		}

		//Return the target calories
		$retArray['target'] = $dri['target'];

		return $retArray;
	}



	public function searchFoods($food) {
		// Search the nutrition database for food matching the input
		// food name using a 'LIKE' clause

		// Upcase input and strip leading and trailing blanks
		$sf = strtoupper($food);
		$sf = preg_replace("/^\\s+/", "", $sf);
		$sf = preg_replace("/\\s+$/", "", $sf);

		// Split input into separate words so the order doesn't matter
		$words = preg_split("/\\s+/", $sf);

		// Build the WHERE clause with all the words in LIKE phrases
		$where = "WHERE (";

		foreach ($words as $word) {
			if (strcasecmp($where, "WHERE (") == 0) {
				$and = "";
			}
			else {
				$and = " AND ";
			}
			$where .= $and . "fd.FoodNameNormalized LIKE '%". $word . "%'";
		}
		$where .= ") ";

		// Issue the SELECT
		try {
			$sql = "SELECT concat(fd.FoodName, ' (', srv.ServingAmountValue, ' ', unit.UnitName, ') (', nut.NutrientValue, ' calories)') as display, fd.FoodName as selected, fd.FoodID as value, srv.ServingTypeID as servingSize, unit.UnitID as UnitID, 0 as custom ".
			 			"FROM p_food AS fd ".
			 			"JOIN p_food_serving_types as srv on fd.FoodID = srv.FoodID ".
			 			"JOIN p_food_nutrients as nut on fd.FoodID = nut.FoodID ".
			 			"JOIN p_food_unit as unit on unit.UnitID = srv.ServingAmountUnitID ".
						$where .
			 			"AND fd.Discontinued = 0 ".
			 			"AND nut.NutrientID = 1 ".
			 			"AND srv.ServingTypeID = 1";
			$data = $this->dbOb->query($sql);
		}
		catch (Exception $e) {
			throw ($e);
		}
		return $data;
	}


	public function saveFoodLog($action, $fid, $log) {
		// Either insert a new food log entry or update
		// an existing one.  Input $action determines what to do.

		$today = date('Y-m-d h:i:s');
		if ($action == "add") {
			if (!isset($log['foodID']) || $log['foodID'] == "") {
				return;
			}
			try {
				$sql = "INSERT INTO u_food_log " .
							"(date_entered, date_updated, is_active, z_user_id, FoodID, ServingTypeID, UnitID, MealID, custom) " .
							"VALUES ('". $log['date_entered'] . "', '" .
							$today . "', 1, " .
							$this->id . ", " .
							$this->dbOb->escape_string($log['foodID']) . ", " .
							$this->dbOb->escape_string($log['serving']) . ", " .
							$this->dbOb->escape_string($log['unit']) . ", " .
							$this->dbOb->escape_string($log['selected_meal']) . ", " .
							$this->dbOb->escape_string($log['custom']) . ")";
				$flID = $this->dbOb->insert($sql);
			}
			catch (Exception $e) {
				throw ($e);
			}

			//
			// The meal plans in the database have serving sizes and units that differ from what is
			// stored in the database under each food.  In some cases there is no serving that matches
			// what is in the meal plan.  What we do here is to look for a difference in unit ID and/or
			// serving size from the standard serving (ID = 1) and adjust the row we just entered with
			//the standard serving unit and an ajusted size.  This involves a conversion (sometimes)
			//like tablespoons to cups.
			//
			if (isset($log['srv'])) {
				$sql = "SELECT ServingAmountValue AS amt, ServingAmountUnitID AS unit FROM p_food_serving_types " .
								"WHERE FoodID = " . $log['foodID'] . " AND ServingTypeID = " . $log['serving'];
				$serv = $this->dbOb->getRow($sql);
				$size = 0;
				$u = 0;
				if ($serv) {
					if ($serv['unit'] != $log['unit']) {
						$size = $this->ConvertSize($log['srv'], $log['unit'], $serv['unit']);
						$u = $serv['unit'];
					}
					else {
						if ($serv['amt'] != $log['srv']) {
							$size = $log['srv'];
						}
					}
				}

				if ($size > 0) {
					if ($u > 0) {
						$unt = ", UnitID = " . $u . " ";
					}
					else {
						$unt = "";
					}
					$sql = "UPDATE u_food_log SET ServingSize = " . $size . $unt . " WHERE id = " . $flID;
					$this->dbOb->update($sql);
				}
			}
		}

		else if ($action == "upd") {
			try {
				$sql = "UPDATE u_food_log " .
							"SET date_updated = '" . $today . "', " .
							"ServingSize = '" . $this->dbOb->escape_string($log['srv']) . "' " .
							"WHERE id = " . $this->dbOb->escape_string($fid);
				$this->dbOb->update($sql);
			}
			catch (Exception $e) {
				throw($e);
			}
		}

		else if ($action == "del") {
			try {
				$sql = "UPDATE u_food_log " .
								"SET is_active = 0, " .
								"date_updated = '" . $today . "' " .
								"WHERE id = " . $this->dbOb->escape_string($fid);
				$this->dbOb->update($sql);
			}
			catch (Exception $e) {
				throw ($e);
			}
		}
		return;
	}


	private function ConvertSize($size, $unit, $stdUnit) {

		//This table converts Volume and Weight measurments
		//The volume measurements are based on 1 teaspoon,
		//the weight is based on 1 gram.  The table is intertwined
		//because the database is intertwined and the index to the
		//array is the id from the database (p_food_units)

		$cvt = array ('5' => 1,				//1 tsp (v)
		              '6' => 3,				//1 tbs (v)
		              '7' => 6,				//1 fl oz (v)
		              '8' => 48,			//1 cup (v)
		              '9' => 192,			//1 qt (v)
		              '10' => 768,		//1 gal (v)
		              '11' => 28.3,		//1 oz (w)
		              '12' => 202.9,	//1 liter (v)
		              '13' => .001,		//1 miligram (w)
		              '14' => 20.3,		//100 ml (v)
		              '15' => 453.6,	//1 pound (w)
		              '16' => 1000,		//1 kilogram (w)
		              '17' => 1,			//1 gram (w)
		              '18' => .203,		//1 ml (v)
		              '30' => 96,			//1 pint (v)
		              '32' => .000001,//uGram (w)
		              '36' => .001);	//100grams (w)

		$sz = $size * ($cvt[$unit] / $cvt[$stdUnit]);
		return $sz;
	}


	/**
	 * addComment()
	 *	@params - $action - "add" or "upd"
	 *						$dte - date entered
	 *						$comment - comment string
	 */
	public function addComment($action, $dte, $comment) {
		$str=new StringUtil();
		$comment = $str->sanitize_data($comment,2);

		if ($action == "add") {
			$sql = "INSERT INTO u_food_log_comments(z_user_id,comment,date_entered) VALUES (" .
		 		"'" . $this->id . "', " .
		 		"'" . $comment . "', " .
		 		"'" . $this->dbOb->escape_string($dte) . "')";
			$this->dbOb->insert($sql);
		}
		else if ($action == "upd") {
			$today = date('Y-m-d h:i:s');
			$sql = "UPDATE u_food_log_comments SET comment = " .
				"'" . $comment . "', " .
				"date_updated = '" . $today . "' " .
				"WHERE z_user_id = '" . $this->id . "' " .
				"AND date_entered = '" . $this->dbOb->escape_string($dte) . "'";
			$this->dbOb->update($sql);
		}		
	}

	/**
	 * getComments()
	 *	@params - $dte - date entered
	 */
	public function getComments($dte) {
		$sql = "SELECT * from u_food_log_comments " .
					"WHERE z_user_id = " . $this->id . " " .
					"AND date_entered = '" . $this->dbOb->escape_string($dte) . "'";
		return $this->dbOb->getRow($sql);
	}
}