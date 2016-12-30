<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");
	require_once (LIB_ROOT."classes/common/UserCredentials.class.php");


	/**
	*	@abstract - This is the Meal Planner model providing access to
	*							all necessary database tables and calculations.
	* @author		- S.LePage
	* @version	- 1.1
	*/

class MealPlanModel {

	private $dbOb = null;
	private $id = 0;


	public function __construct($altId = 0) {
		$this->dbOb = Database::create();
		$cred = UserCredentials::load();
		if (!$altId) {
			$this->id = $this->dbOb->escape_string($cred->getId());
		}
		else { 
			$this->id = $this->dbOb->escape_string($altId);
		}
	}


	public function getUserDefinedMenus() {
		$sql = "SELECT id AS value, name AS display FROM u_food_menu " .
						"WHERE z_user_id = " . $this->id . " " .
						"AND is_active = 1";
		$menus = $this->dbOb->query($sql);
		return $menus;
	}


	public function getUserMenuName($menuID) {
		$sql = "SELECT name FROM u_food_menu " .
						"WHERE id = " . $this->dbOb->escape_string($menuID) . " " .
						"AND is_active = 1";
		$name = $this->dbOb->getOne($sql);
		return $name;
	}


	public function getUserMenuDetails($menuid) {
		$sql = "SELECT uf.DayID, CONCAT('Day ', uf.DayID) AS DayName, " .
						"uf.MealID, m.MealOrder, m.MealName, uf.FoodID, fd.FoodName, " .
						"uf.ServingAmount, uf.ServingSize, uf.UnitID, u.UnitName " .
						"FROM u_food_menu_foods AS uf " .
						"JOIN p_food AS fd ON uf.FoodID = fd.FoodID " .
						"JOIN p_food_meal AS m ON uf.MealID = m.MealID " .
						"JOIN p_food_unit AS u ON uf.UnitID = u.UnitID " .
						"WHERE uf.MenuID = " . $this->dbOb->escape_string($menuid) . " " .
						"AND uf.is_active = 1 " .
						"ORDER BY uf.DayID, m.MealOrder";
		$menu = $this->dbOb->query($sql);

		$did = 0;
		$mid = 0;
		$dayCount = 0;
		$mealCount = 0;
		$foodCount = 0;
		$retArray = array('days' => array());
		if ($menu) {
			foreach ($menu as $m) {
				if ($m['MealID'] != $mid) {
					if ($mid != 0) {
						array_push($darr['meals'], $marr);
						$mealCount += 1;
					}
					$marr = array('id' => $m['MealID'],
					              'order' => $m['MealOrder'],
					              'name' => $m['MealName'],
					              'foods' => array());
					$mid = $m['MealID'];
				}

				if ($m['DayID'] != $did) {
					if ($did != 0) {
						array_push($retArray['days'], $darr);
						$dayCount += 1;
					}
					$darr = array('id' => $m['DayID'],
					              'name' => $m['DayName'],
					              'meals' => array());
					$did = $m['DayID'];
				}

				array_push($marr['foods'], array('FoodID' => $m['FoodID'],
				                                 'FoodName' => $m['FoodName'],
				                                 'SrvAmt' => $m['ServingAmount'],
				                                 'SrvSize' => $m['ServingSize'],
				                                 'UnitID' => $m['UnitID'],
			  	                               'UnitName' => $m['UnitName']));
				$foodCount += 1;
				if (strlen($m['FoodName']) > 40) {
					$foodCount += 1;
				}
			}

			if (count($marr) > 0) {
				array_push($darr['meals'], $marr);
				$mealCount += 1;
			}
			if (count($darr) > 0) {
				array_push($retArray['days'], $darr);
				$dayCount += 1;
			}
		}

		//Seat of the pants calculation for the height of the <iframe>
		//	3 lines for every day (2 extra at the end of the day)
		//	3 lines for every meal (before and after)
		//	1 line per food item (wrapping acounted for above)
		//	20 pixels per line
		$size = (($dayCount * 3) + ($mealCount * 3) + $foodCount) * 20;
		$retArray['vSize'] = $size;
		return $retArray;
	}


	public function CreateUserMenu($name, $items) {
		$sql = "SELECT id FROM u_food_menu " .
						"WHERE name = '" . $this->dbOb->escape_string($name) . "' " .
						"AND z_user_id = " . $this->id . " " .
						"AND is_active = 1";
		$menuID = $this->dbOb->getOne($sql);
		if ($menuID == 0) {
			$sql = "INSERT INTO u_food_menu " .
							"(name, is_active) VALUES ('" . $this->dbOb->escape_string($name) . "', 1)";
			$menuID = $this->dbOb->insert($sql);
		}

		foreach ($items as $item) {
			$sql = "SELECT * FROM u_food_menu_foods " .
							"WHERE MenuID = " . $menuID . " " .
							"AND DayID = " . $this->dbOb->escape_string($item['DayID']) . " " .
							"AND MealID = " . $this->dbOb->escape_string($item['MealID']);
			$flist = $this->dbOb->query($sql);
			

				$sql = "INSERT INTO u_food_menu_foods " .
								"(menuID, DayID, MealID, FoodID, ServingAmount, ServingSize, UnitID) " .
								"VALUES (" . $menuID . ", " .
								$this->dbOb->escape_string($item['DayID']) . ", " .
								$this->dbOb->escape_string($item['MealID']) . ", " .
								$this->dbOb->escape_string($item['FoodID']) . ", " .
								$this->dbOb->escape_string($item['ServingAmount']) . ", " .
								$this->dbOb->escape_string($item['ServingSize']) . ", " .
								$this->dbOb->escape_string($item['UnitID']) . ")";
				$this->dbOb->insert($sql);
			}
		}
		
	public function getMenuTypes() {
		$sql = "SELECT DISTINCT Category FROM p_food_menu_template";
		$mt = $this->dbOb->query($sql);
		$menuType = array();
		foreach($mt as $m) {
			array_push($menuType, array('value' => $m['Category'], 'display' => $m['Category']));
		}
		return $menuType;
	}


	public function getMenuDays() {
		$sql = "SELECT DISTINCT Days FROM p_food_menu_template";
		$days = $this->dbOb->query($sql);
		$menuDays = array();
		foreach($days as $d) {
			array_push($menuDays, array('value' => $d['Days'], 'display' => $d['Days']));
		}
		return $menuDays;
	}


	public function getMenuCalories() {
		$sql = "SELECT DISTINCT Calories FROM p_food_menu_template";
		$cals = $this->dbOb->query($sql);
		$menuCals = array();
		foreach($cals as $c) {
			array_push($menuCals, array('value' => $c['Calories'], 'display' => $c['Calories']));
		}
		return $menuCals;
	}


	public function getAllCannedMenus() {
		$sql = "SELECT * FROM p_food_menu_template";
		$menus = $this->dbOb->query($sql);

		return $menus;
	}

	public function getCannedMenus($type, $days, $cals) {

		$where = "WHERE ";
		$and = "";
		if ($type != "0") {
			$where .= "Category = '" . $type . "' ";
			$and = "AND ";
		}
		if ($days != "0") {
			$where .= $and . "Days = " . $days . " ";
			$and = "AND ";
		}
		if ($cals != "0") {
			$where .= $and . "Calories = " . $cals . " ";
		}

		if ($where == "WHERE ") {
			$where = "";
		}

		$sql = "SELECT MenuTemplateID as value, MenuTemplateName as display " .
						"FROM p_food_menu_template " . $where;
		$menus = $this->dbOb->query($sql);

		return $menus;
	}

	public function getCannedMenu($mindex) {
		$sql = "SELECT mt.MenuTemplateID, d.DayID, d.DayName, m.MealID, mn.MealOrder, mn.MealName, fd.FoodID, fd.FoodText, fd.ServingAmountValue, fd.ServingAmountUnitID " .
						"FROM `p_food_menu_template` AS mt " . 
						"JOIN p_food_menu_template_days AS d ON mt.MenuTemplateID = d.MenuTemplateID " .
						"JOIN p_food_menu_template_meals AS m ON mt.MenuTemplateID = m.MenuTemplateID AND d.DayOrder = m.DayOrder " .
						"JOIN p_food_meal AS mn ON m.MealID = mn.MealID " .
						"JOIN p_food_menu_template_foods AS fd on mt.MenuTemplateID = fd.MenuTemplateID AND d.DayOrder = fd.DayOrder AND m.MealID = fd.MealID " .
						"WHERE mt.MenuTemplateID = " . $this->dbOb->escape_string($mindex) . " " .
						"ORDER BY d.DayOrder, mn.MealOrder, fd.MenuItemOrder";
		$menu = $this->dbOb->query($sql);

		foreach ($menu as &$m) {
			$sql = "SELECT COUNT(*) FROM p_food_recipe WHERE FoodID = " . $m['FoodID'] . " AND RecipeTypeID = 1";
			$c = $this->dbOb->getOne($sql);
			if ($c > 0) {
				$m['recipe'] = 1;
			}
			else {
				$m['recipe'] = 0;
			}
		}

		$days = array();
		$did = 0;
		$mid = 0;
		foreach ($menu as $m) {
			if ($mid == 0) {
				$mid = $m['MealID'];
				$marr = array('id' => $m['MealID'],
				              'order' => $m['MealOrder'],
											'name' => $m['MealName'],
											'foods' => array());
			}

			if ($did == 0) {
				$did = $m['DayID'];
				$darr = array('id' => $m['DayID'],
											'name' => $m['DayName'],
											'meals' => array());
			}

			if ($mid != $m['MealID']) {
				$mid = $m['MealID'];
				array_push($darr['meals'], $marr);
				$marr = array('id' => $m['MealID'],
				              'order' => $m['MealOrder'],
											'name' => $m['MealName'],
											'foods' => array());
				if ($did != $m['DayID']) {
					$did = $m['DayID'];
					array_push($days, $darr);
					$darr = array('id' => $m['DayID'],
												'name' => $m['DayName'],
												'meals' => array());
				}
			}
			
			array_push($marr['foods'], array ('FoodID' => $m['FoodID'],
			              										'ServingSize' => $m['ServingAmountValue'],
			              										'UnitID' => $m['ServingAmountUnitID'],
			              										'Text' => $m['FoodText'],
			              										'Recipe' => $m['recipe']));
		}
		if (count($marr) > 0) {
			array_push($darr['meals'], $marr);
		}
		if (count($darr) > 0) {
			array_push($days, $darr);
		}

		$sql = "SELECT Calories, Days, Category FROM p_food_menu_template " .
						"WHERE MenuTemplateID = " . $this->dbOb->escape_string($mindex);
		$menuName = $this->dbOb->getRow($sql);

		$retArray = array('days' => $days,
											'menu' => $menuName);
		return $retArray;
	}


	public function getMealNames() {
		$sql = "SELECT MealID, MealName FROM p_food_meal ORDER BY MealOrder";
		$meals = $this->dbOb->query($sql);
		return $meals;
	}


	public function getMealPlan ($date = "0000-00-00") {
		if ($date == "0000-00-00") {
			$date = date('Y-m-d');
		}
		$sql = "SELECT * from u_meal_plan " .
							"WHERE z_user_id = " . $this->id . " " .
							"AND is_active = 1 " .
							"AND date_entered = '" . $this->dbOb->escape_string($date) . "'";
		$mplan = $this->dbOb->getRow($sql);
		return $mplan;
	}


	public function saveMealPlan ($action, $mid, $plan) {

		$today = date("Y-m-d H:i:s");

		if ($action == "add") {
			$mName = isset($plan['MenuName']) ? trim($plan['MenuName']) : "";
			if ($mName == "") {
				return 0;
			}

			$sql = "INSERT INTO u_food_menu (z_user_id, name, is_active, date_added) " .
							"VALUES (" . 
							$this->id . ", '" .
							$this->dbOb->escape_string($mName) . "', 1, '" .
							$today . "')";
			$menuID = $this->dbOb->insert($sql);
			if ($menuID == 0) {
				return 0;
			}

			$day = 1;
			$meal = 1;
			$food = 1;
			while($day <= 7) {
				while ($meal <= 6) {
					while (true) {
						if (isset($plan['targetFid'.$day.'_'.$meal.'_'.$food])) {

							$sql = "INSERT INTO u_food_menu_foods " .
											"(MenuID, DayID, MealID, FoodID, ServingSize, UnitID, is_active) " .
											"VALUES (" .
											$menuID . ", " .
											$day . ", " .
											$meal . ", " .
											$plan['targetFid'.$day.'_'.$meal.'_'.$food] . ", " .
											$plan['targetSrv'.$day.'_'.$meal.'_'.$food] . ", " .
											$plan['targetUnt'.$day.'_'.$meal.'_'.$food] . ", 1)";
							$this->dbOb->insert($sql);

							$food += 1;
						}
						else {
							break;
						}
					}

					$meal += 1;
					$food = 1;
				}

				$day += 1;
				$meal = 1;
			}
		}

		return $menuID;
	}



	public function getRecipe($fid) {
		$sql = "SELECT r.*, f.FoodName, m.PreparationMethodName AS method FROM p_food_recipe AS r " .
						"JOIN p_food_preparation_method AS m ON r.PreparationMethodID = m.PreparationMethodID " .
						"JOIN p_food AS f ON r.FoodID = f.FoodID " .
						"WHERE r.FoodID = " . $this->dbOb->escape_string($fid);
		$head = $this->dbOb->getRow($sql);

		$sql = "SELECT rf.*, f.FoodName, u.UnitName, m.PreparationMethodName FROM p_food_recipe_foods AS rf " .
						"JOIN p_food AS f ON f.FoodID = rf.RecipeFoodID " .
						"JOIN p_food_unit AS u ON rf.AmountUnitID = u.UnitID " .
						"LEFT JOIN p_food_preparation_method AS m ON rf.PreparationMethodID = m.PreparationMethodID " .
						"WHERE rf.FoodID = " . $this->dbOb->escape_string($fid);
		$ingredients = $this->dbOb->query($sql);

		$sql = "SELECT ins.* FROM p_food_recipe_Instructions AS ins " .
						"WHERE ins.FoodID = " . $this->dbOb->escape_string($fid) . " " .
						"ORDER BY ins.LineNumber";
		$instructions = $this->dbOb->query($sql);
		foreach ($instructions as &$ins) {
			$ins = preg_replace("/\?/", "", $ins);
		}

		$sql = "SELECT n.* FROM p_food_recipe_notes AS n " .
						"WHERE n.FoodID = " . $this->dbOb->escape_string($fid) . " " .
						"ORDER BY n.LineNumber";
		$notes = $this->dbOb->query($sql);

		$result = array("head"          => $head,
		                "ingredients"   => $ingredients,
		                "instructions"  => $instructions,
		                "notes"         => $notes);
		return $result;						
	}
}
