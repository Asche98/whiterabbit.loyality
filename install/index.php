<?

IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\ModuleManager;
use \Bitrix\Iblock;
use \Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
Class Whiterabbit_loyality extends CModule
{
	function __construct()
	{
		$this->MODULE_VERSION = "1.0.0";
		$this->MODULE_VERSION_DATE = "20.07.2025";
		$this->MODULE_ID = 'whiterabbit.loyality';
		$this->MODULE_NAME = "Модуль для системы лояльности";
		$this->MODULE_DESCRIPTION = "Модуль для системы лояльности";
	}

	function DoInstall()
	{
		if (Loader::includeModule("iblock")) {
			$iblockId = $this->createIblock();
			if ($iblockId) {
				COption::SetOptionInt($this->MODULE_ID, 'iblock_id', $iblockId);
				$this->createIblockProperties($iblockId);
				RegisterModuleDependences(
					'main',
					'OnAfterUserRegister',
					$this->MODULE_ID,
					'WhiteRabbit\Loyality\LoyalityManager',
					'OnAfterUserRegisterHandler'
				);
				RegisterModuleDependences(
					'sale',
					'OnOrderAdd',
					$this->MODULE_ID,
					'WhiteRabbit\Loyality\LoyalityManager',
					'OnSaleOrderSavedHandler'
				);
			}
		}
		\Bitrix\Main\ModuleManager::RegisterModule($this->MODULE_ID);
		return true;
	}

	function DoUninstall()
	{
		if (Loader::includeModule("iblock")) {
			$this->deleteIblockType();
		}
		\Bitrix\Main\ModuleManager::UnRegisterModule($this->MODULE_ID);
		return true;
	}
	private function createIblockType()
	{
		$iblockType = new CIBlockType();
		$typeFields = [

			'ID' => 'loyalty',
			'SECTIONS' => 'Y',
			'IN_RSS' => 'N',
			'SORT' => 500,
			'LANG' => [
				'ru' => [
					'NAME' => 'Лояльность',
					'SECTION_NAME' => 'Секции',
					'ELEMENT_NAME' => 'Элементы',
				],
				'en' => [
					'NAME' => 'Loyalty',
					'SECTION_NAME' => 'Sections',
					'ELEMENT_NAME' => 'Elements',
				],
			],
		];

		if ($iblockType->Add($typeFields)) {
			return true; // Тип успешно создан
		} else {
			echo "Ошибка при создании типа инфоблока: " . $iblockType->LAST_ERROR;
			return false; // Ошибка при создании типа инфоблока
		}
	}

	private function createIblock()
	{
		CModule::IncludeModule('iblock');
		if (!$this->createIblockType()) {
			return; // Завершаем выполнение, если не удалось создать тип
		}

		$iblock = new CIBlock();
		$rsSites = CSite::GetList($by="sort", $order="desc", array("SERVER_NAME"=>$_SERVER['SERVER_NAME']));
		while ($arSite = $rsSites->Fetch())
		{
			$arSites[] = $arSite["LID"];
		}
		$arFields = [
			"SITE_ID" => $arSites,
			'ACTIVE' => 'Y',
			'NAME' => 'Система лояльности',
			'CODE' => 'LOYALTY_SYSTEM',
			'LIST_PAGE_URL' => '',
			'DETAIL_PAGE_URL' => '',
			'IBLOCK_TYPE_ID' => 'loyalty', // Используем новый тип
			'GROUP_ID' => ['2' => 'R'], // Права доступа для группы "Все пользователи"
		];

		$iblockId = $iblock->Add($arFields);
		if ($iblockId) {
			return $iblockId;
		} else {
			return  $iblock->LAST_ERROR; // Выводим сообщение об ошибке
		}

		return false;
	}

	private function deleteIblockType()
	{
		$iblockTypeCode = 'loyalty'; // Код типа инфоблока, который нужно удалить

		// Проверяем существует ли тип инфоблока
		$iblockType = CIBlockType::GetByID($iblockTypeCode);
		if ($iblockType = $iblockType->Fetch()) {
			// Если тип существует, удаляем его
			if (CIBlockType::Delete($iblockTypeCode)) {
				return true; // Тип успешно удален
			} else {
				echo "Ошибка при удалении типа инфоблока: " . CIBlockType::LAST_ERROR;
				return false; // Ошибка при удалении типа инфоблока
			}
		} else {
			echo "Тип инфоблока не найден.";
			return false; // Тип инфоблока не найден
		}
	}
	private function createIblockProperties($iblockId)
	{
		$property = new CIBlockProperty();
		$arFieldsBonuses = [
			'NAME' => 'Количество баллов',
			'ACTIVE' => 'Y',
			'SORT' => 100,
			'CODE' => 'QUANTITY',
			'PROPERTY_TYPE' => 'N', // тип 'Число'
			'IBLOCK_ID' => $iblockId,
		];
		$property->add($arFieldsBonuses);

		// Создание второго свойства (привязка к пользователю)
		$property = new CIBlockProperty();
		$arFieldsUser = [
			'NAME' => 'Пользователь',
			'ACTIVE' => 'Y',
			'SORT' => 200,
			'CODE' => 'USER',
			'PROPERTY_TYPE' => 'N', // тип 'Привязка к элементам'
			'USER_TYPE' => 'UserID',
			'IBLOCK_ID' => $iblockId,
		];
		$property->add($arFieldsUser);

		$property = new CIBlockProperty();
		$arFieldsLog = [
			'NAME' => 'Лог',
			'ACTIVE' => 'Y',
			'SORT' => 200,
			'CODE' => 'LOG',
			'PROPERTY_TYPE' => 'S',
			'MULTIPLE' => 'Y',
			'IBLOCK_ID' => $iblockId,
		];
		$property->add($arFieldsLog);
	}

	private function deleteIblock()
	{
		$iblockTypeId = 'loyalty';
		$iblocks = Iblock\IblockTable::getList([
			'filter' => ['IBLOCK_TYPE_ID' => $iblockTypeId],
		]);

		while ($iblock = $iblocks->fetch()) {
			Iblock\IblockTable::delete($iblock['ID']);
		}
	}
}