<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");
	require_once (LIB_ROOT."classes/common/UserCredentials.class.php");

	/**
	*	@abstract - This is the model for manipulating user defined custom foods.
	* @author - S.LePage
	* @version - 1.1
	*/


class CustomFoodsModel {

	private $dbOb = null;
	private $id = 0;

	// Constructor
	public function __construct ($altId = 0) {
		$this->dbOb = Database::create();
		$cred = UserCredentials::load();
		if (!$altId) {
			$this->id = $this->dbOb->escape_string($cred->getId());
		}
		else {
			$this->id = $this->dbOb->escape_string($altId);
		}
	}


	// Search for food in the user's list with names that are similar to
	// the input string.  Note that the string is broken into separate words
	// so that "coffee brewed" will find the same as "brewed coffee"
	public function searchCustomFoods($food) {
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

		try {
			$sql = "SELECT concat(fd.FoodName, ' (', srv.ServingAmountValue, ' ', unit.UnitName, ') (', nut.NutrientValue, ' calories)') as display, fd.FoodName as selected, fd.FoodID as value, 1 as servingSize, unit.UnitID as UnitID, 1 as custom " .
							"FROM u_custom_foods AS fd " .
							"JOIN u_custom_foods_serving_types AS srv ON fd.FoodID = srv.FoodID " .
							"JOIN u_custom_foods_nutrients AS nut ON fd.FoodID = nut.FoodID " .
							"JOIN p_food_unit AS unit ON unit.UnitID = srv.ServingAmountUnitID " .
							$where .
							"AND fd.is_active = 1 " .
							"AND nut.NutrientID = 1 " .
							"AND fd.z_user_id = " . $this->id;
			$data = $this->dbOb->query($sql);
		}
		catch (Exception $e) {
			throw($e);
		}

		return $data;
	}


	// Return a list of food nutrients for the input custom food
	public function getFoodNutrients($food) {
		$sql = "SELECT fd.FoodID, nut.NutrientValue, srv.ServingAmountValue, unit.UnitName " .
						"FROM u_custom_foods as fd " .
						"Join u_custom_foods_nutrients AS nut ON fd.FoodID = nut.FoodID " .
						"JOIN u_custom_foods_serving_types AS srv ON fd.FoodID = srv.FoodID " .
						"JOIN p_food_unit AS unit ON unit.UnitID = srv.ServingAmountUnitID " .
						"WHERE fd.FoodID = " . $this->dbOb->escape_string($food);
		$list = $this->dbOb->query($sql);
		return $list;
	}


	// Return a list of food class ids and class names.  If the input parent
	// class is zero, then return all top level classes (parent = 0).  Otherwise
	// return all subordinate classes of the input parent.
	public function getFoodClasses ($parent = 0) {
		if ($parent == 0) {
			$sql = "SELECT FoodClassID as value, FoodClassFullName as display FROM p_food_class " .
								"WHERE FoodClassParentID = 0";
		}
		else {
			$sql = "SELECT FoodClassID as value, FoodClassFullName as display FROM p_food_class " .
								"WHERE FoodClassParentID <> 0 " .
								"AND FoodClassFullID LIKE '|" . $this->dbOb->escape_string($parent) . "|%'";
		}

		$list = $this->dbOb->query($sql);
		return $list;
	}


	// Get a long list of nutrients - id, name, abbreviation, and unit name
	public function getNutrientsList() {
		$sql = "SELECT n.NutrientID, n.NutrientName, n.Abbreviation, u.UnitName " .
						"FROM p_food_nutrient AS n " .
						"JOIN p_food_unit AS u ON n.UnitID = u.UnitID";
		$nutrients = $this->dbOb->query($sql);
		return $nutrients;
	}


	// Get list of serving unit names
	public function getUnits() {
		$sql = "SELECT UnitID as value, UnitName as display FROM p_food_unit " .
						"WHERE UnitTypeID IN (1, 3, 4, 5)";
		$units = $this->dbOb->query($sql);
		return $units;
	}


	// Insert a food row into the custom foods table as well as all of the
	// the associated nutrient rows for this food
	public function addCustomFoods($food, $nutrients) {

		$normName = strtoupper($food['food_name']);

		try {
			$sql = "INSERT INTO u_custom_foods " .
							"(is_active, z_user_id, FoodClassId, FoodName, FoodNameNormalized) " .
							"VALUES (1, " .
							$this->id . ", " .
							$this->dbOb->escape_string($food['food_class_id']) . ", '" .
							$this->dbOb->escape_string($food['food_name']) . "', '" .
							$this->dbOb->escape_string($normName) . "')";
			$idx = $this->dbOb->insert($sql);
		}
		catch (Exception $e) {
			throw ($e);
		}

		try {
			$sql = "INSERT INTO u_custom_foods_serving_types " .
							"(FoodID, ServingAmountUnitID, ServingAmountValue, GramWeightValue, ServingAmountNote) " .
							"VALUES (" . $idx . ", " .
							$this->dbOb->escape_string($food['unit_id']) . ", '" .
							$this->dbOb->escape_string($food['serving_amount']) . "', '" .
							$this->dbOb->escape_string($food['serving_gram_wt']) . "', '" .
							$this->dbOb->escape_string($food['serving_note']) . "')";
			$this->dbOb->insert($sql);
		}
		catch (Exception $e) {
			$rm = "DELETE FROM u_custom_foods WHERE id = " . $idx;
			$this->dbOb->query($rm);
			throw($e);
		}

		foreach($nutrients as $nutId => $val) {
			try {
				$sql = "INSERT INTO u_custom_foods_nutrients " .
								"(FoodID, NutrientID, NutrientValue) " .
								"VALUES (" . $idx . ", " .
								$this->dbOb->escape_string($nutId) . ", " .
								$this->dbOb->escape_string($val) . ")";
				$this->dbOb->insert($sql);
			}
			catch (Exception $e) {
				$rm = "DELETE FROM u_custom_foods WHERE id = " . $idx;
				$this->dbOb->query($rm);
				$rm = "DELETE FROM u_custom_foods_serving_types WHERE FoodID = " . $idx;
				$this->dbOb->query($rm);
				$rm = "DELETE FROM u_custom_foods_nutrients WHERE FoodID = " . $idx;
				$this->dbOb->query($rm);
				throw ($e);
			}
		}
	}


	// Update the food name, food class, serving info and nutrient values for an input food
	public function updateCustomFoods($food, $nutrients) {
		$today = date("Y-m-d h:i:s");
		$normName = strtoupper($food['food_name']);

		$sql = "UPDATE u_custom_foods SET date_updated = '" . $today . "', " .
						"FoodName = '" . $this->dbOb->escape_string($food['food_name']) . "', " .
						"FoodClassId = " . $this->dbOb->escape_string($food['food_class_id']) . ", " .
						"FoodNameNormalized = '" . $this->dbOb->escape_string($normName) . "' " .
						"WHERE FoodID = " . $this->dbOb->escape_string($food['id']);
		$this->dbOb->update($sql);

		$sql = "UPDATE u_custom_foods_serving_types " .
						"SET ServingAmountUnitID = " . $this->dbOb->escape_string($food['unit_id']) . ", '" .
						"ServingAmountValue = " . $this->dbOb->escape_string($food['serving_amount']) . "', " .
						"GramWeightValue = '" . $this->dbOb->escape_string($food['serving_gram_wt']) . "', " .
						"ServingAmountNote = '" . $this->dbOb->escape_string($food['serving_note']) . "' " .
						"WHERE FoodID = " . $this->dbOb->escape_string($food['id']);
		$this->dbOb->update($sql);
						
		foreach($nutrients as $nut => $val) {
			$sql = "UPDATE u_custom_foods_nutrients SET " .
							"NutrientValue = " . $this->dbOb->escape_string($val) .
							"WHERE NutrientID = " . $this->dbOb->escape_string($nut) . " " .
							"AND FoodID = " . $this->dbOb->escape_string($food['id']);
			$this->dbOb->update($sql);
		}
	}


	// Food deletion is a matter of setting the 'is_active' flag to zero.
	// Note that the associated nutrients and serving data become orphans
	// with no food to reference them.
	public function deleteCustomFoods($food) {
		$today = date("Y-m-d h:i:s");

		$sql = "UPDATE u_custom_foods SET is_active = 0, " .
						"date_updated = '" . $today . "' " .
						"WHERE FoodID = " . $this->dbOb->escape_string($food['id']);
		$this->dbOb->update($sql);
	}
}