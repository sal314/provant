<?php
	require_once (LIB_ROOT.'classes/base/PageBase.class.php');
	require_once (ROOT_DIR.'classes/model/MealPlanModel.php');
	require_once (ROOT_DIR.'classes/model/FoodLogModel.php');
	require_once (LIB_ROOT.'classes/common/Ajax.class.php');
	require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');

	/**
	*	@abstract - The main view class for the Meal Planner
	*	@author - S.LePage
	*	@version - 1.1
	*/

class MealPlan extends PageBase {

	private $mpm = null;
	private $utm = null;

	public function __construct ($altId = 0) {
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header('Location: /Landing/Index');
			exit();
		}

		$this->utm = new UserTrackerModel();

		if ($altId == 0) {
			$this->mpm = new MealPlanModel();
		}
		else {
			$this->mpm = new MealPlanModel($altId);
		}

		return;
	}

	public function Index($params) {
		$template = TemplateParser::enqueue(TEMPLATE_DIR.'/frontend/mealplan/index.tpt');
		$um = $this->mpm->getUserDefinedMenus();
		if (count($um) > 0) {
			array_unshift($um, array('value' => 0, 'display' => "-Select a Menu-"));
		}
		$template->addVar("uMenus", $um);

		$track = $this->utm->getLinks();
		$template->addVar("tracker", $track);
		return $template;
	}


	public function Display($params) {
		$template = TemplateParser::enqueue(TEMPLATE_DIR.'/frontend/mealplan/menu_display.tpt');
		$menuID = $_POST['MenuID'];
		$template->addVar('menuID', $menuID);

		$name = $this->mpm->getUserMenuName($menuID);
		$template->addVar("menuName", $name);

		$details = $this->mpm->getUserMenuDetails($menuID);                                                          
		$flm = new FoodLogModel($this->cred->getId());
		$foodLog = $flm->getFoodLog(date('Y-m-d'));
		foreach($details['days'] as &$Day) {
			foreach($Day['meals'] as &$Meals) {
				foreach($Meals['foods'] as &$Food) {
					$Food['logged'] = 0;
				}
			}
		}
		if ($foodLog) {
			foreach($foodLog as $food) {
				foreach($details['days'] as &$Day) {
					foreach($Day['meals'] as &$Meals) {
						foreach($Meals['foods'] as &$Food) {
							if ($Meals['id'] == $food['MealID']) {
								if ($Food['FoodID'] == $food['FoodID']) {
									$Food['logged'] = 1;
								}
							}
						}
					}
				}
			}
		}
		$template->addVar('menuData', $details);

		$track = $this->utm->getLinks();
		$template->addVar("tracker", $track);
		return $template;
	}

	public function ShowMenu($params) {
		$menuID = isset($params[0]) ? $params[0] : 0;
		if ($menuID == 0) {
			exit();
		}

		$template = TemplateParser::enqueue(TEMPLATE_DIR.'/frontend/mealplan/show_menu.tpt');
		$template->addVar('menuID', $menuID);

		$name = $this->mpm->getUserMenuName($menuID);
		$template->addVar("menuName", $name);

		$details = $this->mpm->getUserMenuDetails($menuID);

		$template->addVar('menuData', $details);
		$template->parse();
		exit();
	}


	public function printShoppingList($params) {
		$menuID = isset($params[0]) ? $params[0] : 0;
		if ($menuID == 0) {
			header('Location: /MealPlan/Create');
			exit();
		}
		$flag = isset($params[1]) ? $params[1] : 0;

		$lines = 0;
		$menuFoods = $this->mpm->getUserMenuDetails($menuID);
		$foodList = array();
		foreach($menuFoods['days'] as $day) {
			foreach($day['meals'] as $meal) {
				foreach ($meal['foods'] as $food) {
					if (isset($foodList[$food['FoodID']])) {
						$foodList[$food['FoodID']]['Qty'] += $food['SrvSize'];
					}
					else {
						$foodList[$food['FoodID']] = array('Qty' => $food['SrvSize'],
						                                   'Unit' => $food['UnitName'],
						                                   'Name' => $food['FoodName']);
						$lines += 1;
						if (strlen($food['FoodName']) > 65) {
							$lines += 1;
						}
					}
				}
			}
		}

		$size = $lines * 27 + 60;

		$list = array('items' => $foodList, 'vSize' => $size);
		if ($flag == 0) {
			$template = TemplateParser::create(TEMPLATE_DIR.'frontend/mealplan/shopping_list.tpt');
		}
		else {
			$template = TemplateParser::create(TEMPLATE_DIR.'frontend/mealplan/shopping_frame.tpt');
		}
		$template->addVar('list', $list);
		$template->addVar('menuID', $menuID);
		$template->parse();
		exit();
	}
/*
	public function Create($params) {
//		$menus = getUserMenus();

		$menuType = $this->mpm->getMenuTypes();
		$menuDays = $this->mpm->getMenuDays();
		$menuCalories = $this->mpm->getMenuCalories();

		$template = TemplateParser::enqueue(TEMPLATE_DIR.'/frontend/mealplan/create.tpt');

		array_unshift($menuType, array('value' => "0", 'display' => "-- Select a menu type --"));
		array_unshift($menuDays, array('value' => "0", 'display' => "-- Select number of days --"));
		array_unshift($menuCalories, array('value' => "0", 'display' => "-- Select calories --"));
		$template->addVar("menuType", $menuType);
		$template->addVar("menuDays", $menuDays);
		$template->addVar("menuCalories", $menuCalories);

		$MenuType = "0";
		$MenuDays = "0";
		$MenuCalories = "0";
		if (isset($_POST['MenuType'])) {
			$MenuType = $_POST['MenuType'];
			$MenuDays = $_POST['MenuDays'];
			$MenuCalories = $_POST['MenuCalories'];
			if (!isset($_POST['SrcMenu'])) {
				$srcMenus = $this->mpm->getCannedMenus($_POST['MenuType'], $_POST['MenuDays'], $_POST['MenuCalories']);
				$template->addVar("srcMenus", $srcMenus);
				$template->addVar("menuCount", count($srcMenus));
			}
			else {
				$srcMenu = $this->mpm->getCannedMenu($_POST['SrcMenu']);
				$template->addVar("menuData", $srcMenu['days']);
				$template->addVar("category", $srcMenu['menu']['Category']);
				$template->addVar("ndays", $srcMenu['menu']['Days']);
				$template->addVar("calories", $srcMenu['menu']['Calories']);
			}
		}
		else {
			$srcMenus = $this->mpm->getCannedMenus("0", "0", "0");
			$template->addVar("srcMenus", $srcMenus);
			$template->addVar("menuCount", count($srcMenus));
		}
		$template->addVar("MT", $MenuType);
		$template->addVar("MD", $MenuDays);
		$template->addVar("MC", $MenuCalories);
	
		return $template;
	}
*/
	public function Create($params) {
//		$pr = (date('i') * date('s') * date('d')) % 99;
//		$mName = isset($_POST['UserMenuName']) ? trim($_POST['UserMenuName']) : "";

//		$mName = isset($params[0]) ? $params[0] : "Test Menu " . $pr;

		$menuType = $this->mpm->getMenuTypes();
		$menuDays = $this->mpm->getMenuDays();
		$menuCalories = $this->mpm->getMenuCalories();

		$template = TemplateParser::enqueue(TEMPLATE_DIR.'/frontend/mealplan/create.tpt');

		$um = $this->mpm->getUserDefinedMenus();
		if (count($um) > 0) {
			array_unshift($um, array('value' => 0, 'display' => "-Stored Custom Menus-"));
		}
		else {
			$um = array(array('value' => 0, 'display' => "-Select Custom Menus-"));
		}
		$template->addVar("uMenus", $um);

		array_unshift($menuType, array('value' => "0", 'display' => "-- Select a menu type --"));
		array_unshift($menuDays, array('value' => "0", 'display' => "-- Select number of days --"));
		array_unshift($menuCalories, array('value' => "0", 'display' => "-- Select calories --"));
		$template->addVar("menuType", $menuType);
		$template->addVar("menuDays", $menuDays);
		$template->addVar("menuCalories", $menuCalories);

		$MenuType = "0";
		$MenuDays = "0";
		$MenuCalories = "0";
		if (isset($_POST['MenuType'])) {
			$MenuType = $_POST['MenuType'];
			$MenuDays = $_POST['MenuDays'];
			$MenuCalories = $_POST['MenuCalories'];
			if (!isset($_POST['SrcMenu'])) {
				$srcMenus = $this->mpm->getAllCannedMenus();
				$template->addVar("srcMenus", $srcMenus);
				$template->addVar("menuCount", count($srcMenus));
			}
			else {
				$srcMenu = $this->mpm->getCannedMenu($_POST['SrcMenu']);
				$template->addVar("menuData", $srcMenu['days']);
				$template->addVar("category", $srcMenu['menu']['Category']);
				$template->addVar("ndays", $srcMenu['menu']['Days']);
				$template->addVar("calories", $srcMenu['menu']['Calories']);
			}
		}
		else {
			$srcMenus = $this->mpm->getAllCannedMenus();
			$template->addVar("srcMenus", $srcMenus);
			$template->addVar("menuCount", count($srcMenus));
		}
		$template->addVar("MT", $MenuType);
		$template->addVar("MD", $MenuDays);
		$template->addVar("MC", $MenuCalories);

		$tgMeals = $this->mpm->getMealNames();
		$template->addVar("targetMeals", $tgMeals);

		$track = $this->utm->getLinks();
		$template->addVar("tracker", $track);
		return $template;
	}


	public function GetMenu() {
		$ajax = new Ajax();
		$src = isset($_POST['src']) ? $_POST['src'] : null;
		$str = isset($_POST['value']) ? $_POST['value'] : null;
		if (!$str) {
			$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
			$ajax->writeResponseXML();
			exit;
		}
		try {
			$model = new MealPlanModel();
			$data = $model->getCannedMenu($str);
			$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
			$ajax->addResponseData("values", $data);
			$ajax->addResponseData("src", $src);
		} catch(Exception $e) {
			$ajax->addResponseMessage("Error", Ajax::ERROR, $e->getMessage());
		}
		$ajax->writeResponseXML();
		exit;
	}


	public function SaveUserMenu($params) {
		$mName = isset($_POST['MenuName']) ? trim($_POST['MenuName']) : "";
		if ($mName == "") {
			throw new Exception('Missing data - Menu Name');
			exit;
		}

		$menuID = $this->mpm->saveMealPlan('add', 0, $_POST);
		if ($menuID == 0) {
			throw new Exception('Failed to add a menu header in the database');
			exit;
		}

		header("Location: /MealPlan/Create");
		exit;
	}


	public function LogFood() {
		$ajax = new Ajax();
		$mealID = isset($_POST['mealID']) ? trim($_POST['mealID']) : 0;
		$foodID = isset($_POST['foodID']) ? trim($_POST['foodID']) : 0;
		$srvSize = isset($_POST['srv']) ? trim($_POST['srv']) : 0;
		$unitID = isset($_POST['unit']) ? trim($_POST['unit']) : 0;
		$err = "";

		if ($mealID == 0) {
			$err .= " Meal ID ";
		}
		if ($foodID == 0) {
			$err .= " Food ID ";
		}
		if ($srvSize == 0) {
			$err .= " Serving Size ";
		}
		if ($unitID == 0) {
			$err .= " Unit ID ";
		}

		if (($mealID == 0) || ($foodID == 0) || ($srvSize == 0) || ($unitID == 0)) {
			$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters: " . $err);
			$status = "Failed";
			$ajax->addResponseData("values", $status);
			$ajax->writeResponseXML();
			exit;
		}

		$today = date('Y-m-d') . " 00:00:00";
		$flm = new FoodLogModel();
		$arr = array('foodID' => $foodID,
		             'serving' => 1,
		             'srv' => $srvSize,
		             'unit' => $unitID,
		             'selected_meal' => $mealID,
		             'custom' => 0,
		             'date_entered' => $today);
		try {
			$flm->saveFoodLog('add', 0, $arr);
		}
		catch (Exception $e) {
			$ajax->addResponseMessage("Error",Ajax::ERROR,$e->message);
			$status = "Failed";
			$ajax->addResponseData("values", $status);
			$ajax->writeResponseXML();
			exit;
		}
		$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
		$status = "Success";
		$ajax->addResponseData("values", $status);
		$ajax->writeResponseXML();
		exit;			
	}


	public function ShowRecipe($param) {
		$FoodID = isset($param[0]) ? $param[0] : 0;
		if ($FoodID == 0) {
			throw new Exception('No input Food Identifier for recipe');
			exit();
		}

		$template = TemplateParser::create(TEMPLATE_DIR."/frontend/mealplan/recipe.tpt");
		$recipe = $this->mpm->getRecipe($FoodID);
		$template->addVar("recipe", $recipe);
		$template->parse();
		exit();
	}
}
