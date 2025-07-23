<?php

namespace WhiteRabbit\Loyality;

class LoyalityManager{

	public static $MODULE_ID = 'whiterabbit.loyality';
	public static function OnAfterUserRegisterHandler(&$arFields)
	{
		$iblockId = \COption::GetOptionInt(self::$MODULE_ID, 'iblock_id');
		$userId = $arFields['USER_ID'];
		$bonuses = 500;

		$el = new \CIBlockElement();
		$arLoadProductArray = Array(
			"NAME" => $userId,
			"IBLOCK_ID" => $iblockId,
			"PROPERTY_VALUES" => Array(
				"QUANTITY" => $bonuses,
				"USER" => $userId,
				"LOG" => date('Y-m-d H:i:s') . " + " . $bonuses.' - начисление приветственных бонусов',
			),
		);
		$el->Add($arLoadProductArray);
	}

	public static function OnSaleOrderSavedHandler($id, $arFields)
	{
		$iblockId = \COption::GetOptionInt(self::$MODULE_ID, 'iblock_id');
		$userId = $arFields['USER_ID'];
		$orderSum = $arFields['PRICE'];
		$bonuses = $orderSum * 0.1; // 10% от суммы заказа

		$element = \CIBlockElement::GetList(
			array(),
			array('IBLOCK_ID' => $iblockId, 'PROPERTY_USER' => $userId),
			false,
			false,
			array('ID', 'PROPERTY_QUANTITY', 'PROPERTY_LOG')
		)->Fetch();

		$el = new \CIBlockElement();
		if ($element) {
			$newQuantity = $element['PROPERTY_QUANTITY_VALUE'] + $bonuses;
			$logValues = [];
			// Получаем текущее значение LOG и добавляем новое
			$logValues[] = $element['PROPERTY_LOG_VALUE'];
			$logValues[] = date('Y-m-d H:i:s') . " + " . $bonuses;

			// Обновляем свойства элемента
			\CIBlockElement::SetPropertyValuesEx($element['ID'], $iblockId, array(
				"QUANTITY" => $newQuantity,
				"LOG" => $logValues, // Передаем массив
			));
		} else {
			$arLoadProductArray = array(
				"NAME" => $userId,
				"IBLOCK_ID" => $iblockId,
				"PROPERTY_VALUES" => array(
					"QUANTITY" => $bonuses,
					"USER" => $userId,
					"LOG" => array(date('Y-m-d H:i:s') . " + " . $bonuses), // Создаем массив с первым элементом
				),
			);
			$el->Add($arLoadProductArray);
		}
	}


}