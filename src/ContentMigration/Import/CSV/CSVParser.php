<?php

namespace Drupal\effective_activism\ContentMigration\Import\CSV;

use DateTime;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\Helper\LocationHelper;
use Drupal\effective_activism\ContentMigration\Import\EntityImportParser;
use Drupal\effective_activism\ContentMigration\ParserInterface;
use Drupal\effective_activism\ContentMigration\ParserValidationException;
use Drupal\file\Entity\File;

/**
 * Parses CSV file.
 */
class CSVParser extends EntityImportParser implements ParserInterface {

  const BATCHSIZE = 50;

  const CSVHEADERFORMAT = [
    'start_date',
    'end_date',
    'address',
    'address_extra_information',
    'title',
    'description',
    'results',
  ];

  /**
   * CSV filepath.
   *
   * @var string
   */
  private $filePath;

  /**
   * CSV file.
   *
   * @var resource
   */
  private $fileHandle;

  /**
   * Item count.
   *
   * @var int
   */
  private $itemCount;

  /**
   * Parent group.
   *
   * @var \Drupal\effective_activism\Entity\Group
   */
  private $group;

  /**
   * Import entity.
   *
   * @var \Drupal\effective_activism\Entity\Import
   */
  private $import;

  /**
   * The current row number.
   *
   * @var int
   */
  private $row = 0;

  /**
   * The current column number.
   *
   * @var int
   */
  private $column = 0;

  /**
   * Tracks the latest read event.
   *
   * @var Event
   */
  private $latestEvent;

  /**
   * Tracks the latest read result.
   *
   * @var Result
   */
  private $latestResult;

  /**
   * Any validation error message.
   *
   * @var array
   */
  private $errorMessage;

  /**
   * Creates the CSVParser Object.
   *
   * @param string $file
   *   A CSV file.
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The parent group id of the events.
   * @param \Drupal\effective_activism\Entity\Import $import
   *   The import entity.
   */
  public function __construct($file, Group $group, Import $import = NULL) {
    $this->filePath = File::load($file)->getFileUri();
    $this->group = $group;
    $this->import = $import;
    $this->setItemCount();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $isValid = TRUE;
    try {
      $this->fileHandle = fopen($this->filePath, "r");
      $this->validateHeader();
      $this->validateRows();
      fclose($this->fileHandle);
    }
    catch (ParserValidationException $exception) {
      $isValid = FALSE;
      switch ($exception->getErrorCode()) {
        case self::INVALID_HEADERS:
          $this->errorMessage = t('The CSV file does not contain valid column names. Column names should be "@column_names_format"', [
            '@column_names_format' => implode(',', self::CSVHEADERFORMAT),
          ]);
          break;

        case self::INVALID_DATE:
          $this->errorMessage = t('The CSV file contains a row with an incorrect date (@value) at line @line, column @column.', [
            '@value' => $exception->getValue(),
            '@line' => $exception->getDataLine() + 1,
            '@column' => $exception->getDataColumn(),
          ]);
          break;

        case self::INVALID_LOCATION:
          if (!empty($exception->getExtraInformation())) {
            $this->errorMessage = t('The CSV file contains a row with an incorrect address (@value) at line @line, column @column. Please select from these suggestions or omit the address: @suggestions', [
              '@value' => $exception->getValue(),
              '@line' => $exception->getDataLine() + 1,
              '@column' => $exception->getDataColumn(),
              '@suggestions' => '"' . implode(t('" or "'), $exception->getExtraInformation()) . '"',
            ]);
          }
          else {
            $this->errorMessage = t('The CSV file contains a row with an incorrect address (@value) at line @line, column @column.', [
              '@value' => $exception->getValue(),
              '@line' => $exception->getDataLine() + 1,
              '@column' => $exception->getDataColumn(),
            ]);
          }
          break;

        case self::INVALID_RESULT:
          $this->errorMessage = t('The CSV file contains a row with an incorrect result (@value) at line @line, column @column.', [
            '@value' => $exception->getValue(),
            '@line' => $exception->getDataLine() + 1,
            '@column' => $exception->getDataColumn(),
          ]);
          break;

        case self::INVALID_DATA:
          $this->errorMessage = t('The CSV file contains a row with incorrect data (@value) at line @line, column @column.', [
            '@value' => $exception->getValue(),
            '@line' => $exception->getDataLine() + 1,
            '@column' => $exception->getDataColumn(),
          ]);
          break;

        case self::INVALID_EVENT:
          $this->errorMessage = t('The CSV file contains a row with an incorrect event at line @line.', [
            '@line' => $exception->getDataLine() + 1,
          ]);
          break;

        case self::WRONG_ROW_COUNT:
          $this->errorMessage = t('The CSV file contains a row with incorrect number of columns at line @line.', [
            '@line' => $exception->getDataLine() + 1,
          ]);
          break;

        case self::PERMISSION_DENIED:
          $this->errorMessage = t('The CSV file contains a row with an inaccessable value at line @line, column @column.', [
            '@line' => $exception->getDataLine() + 1,
            '@column' => $exception->getDataColumn(),
          ]);
          break;
      }
    }
    return $isValid;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * Validates the CSV header.
   */
  private function validateHeader() {
    $this->row++;
    if (fgetcsv($this->fileHandle) !== self::CSVHEADERFORMAT) {
      throw new ParserValidationException(self::INVALID_HEADERS, $this->row, $this->convertColumn($this->column));
    }
  }

  /**
   * Validates the CSV data.
   */
  private function validateRows() {
    while (($row = fgetcsv($this->fileHandle)) !== FALSE) {
      // Skip header.
      if ($this->row === 0) {
        $this->row++;
        continue;
      }
      $this->validateRow($row);
      $this->row++;
    }
  }

  /**
   * Validate row.
   *
   * @param array $row
   *   The row to validate.
   */
  private function validateRow(array $row) {
    foreach ($row as $column => $data) {
      $this->column = $column;
      switch (self::CSVHEADERFORMAT[$column]) {
        case 'start_date':
        case 'end_date':
          if (!empty($data)) {
            $date = \DateTime::createFromFormat('Y-m-d H:i', $data);
            if (!$date || $date->format('Y-m-d H:i') !== $data) {
              throw new ParserValidationException(self::INVALID_DATE, $this->row, $this->convertColumn($this->column), $data);
            }
          }
          break;

        case 'address':
          if (!empty($data)) {
            if (!LocationHelper::validateAddress($data)) {
              $suggestions = LocationHelper::getAddressSuggestions($data);
              if (!empty($suggestions)) {
                throw new ParserValidationException(self::INVALID_LOCATION, $this->row, $this->convertColumn($this->column), $data, $suggestions);
              }
              else {
                throw new ParserValidationException(self::INVALID_LOCATION, $this->row, $this->convertColumn($this->column), $data);
              }
            }
          }
          break;

        case 'results':
          $dataArray = array_map('trim', explode('|', $data));
          if (!empty($data) && (count(explode('|', $data)) < 5 || !$this->validateResult($dataArray, reset($dataArray), $this->group))) {
            throw new ParserValidationException(self::INVALID_RESULT, $this->row, $this->convertColumn($this->column), $data);
          }
          break;

        case 'result_data':
          if (!empty($data) && (count(explode('|', $data)) !== 2 || !$this->validateData(array_map('trim', explode('|', $data)), reset(array_map('trim', explode('|', $data)))))) {
            throw new ParserValidationException(self::INVALID_DATA, $this->row, $this->convertColumn($this->column), $data);
          }
          break;

      }
    }
    // Validate event if required fields are present.
    if ($this->isEvent($row)) {
      $values = [
        $row[array_search('title', self::CSVHEADERFORMAT)],
        DateTime::createFromFormat('Y-m-d H:i', $row[array_search('start_date', self::CSVHEADERFORMAT)])->format(DATETIME_DATETIME_STORAGE_FORMAT),
        DateTime::createFromFormat('Y-m-d H:i', $row[array_search('end_date', self::CSVHEADERFORMAT)])->format(DATETIME_DATETIME_STORAGE_FORMAT),
        [
          'address' => $row[array_search('address', self::CSVHEADERFORMAT)],
          'extra_information' => $row[array_search('address_extra_information', self::CSVHEADERFORMAT)],
        ],
        $row[array_search('description', self::CSVHEADERFORMAT)],
        NULL,
        $this->group->id(),
        NULL,
        NULL,
        NULL,
      ];
      if (!$this->validateEvent($values)) {
        throw new ParserValidationException(self::INVALID_EVENT, $this->row, NULL);
      }
    }
  }

  /**
   * Set the number of items to be imported.
   */
  private function setItemCount() {
    $this->itemCount = 0;
    $this->fileHandle = fopen($this->filePath, "r");
    while (fgetcsv($this->fileHandle) !== FALSE) {
      $this->itemCount++;
    }
    fclose($this->fileHandle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount() {
    return $this->itemCount;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextBatch($position) {
    $this->fileHandle = fopen($this->filePath, "r");
    $this->row = 0;
    $itemCount = 0;
    $items = [];
    while (($row = fgetcsv($this->fileHandle)) !== FALSE) {
      // Skip to current row.
      if ($this->row === 0 || $this->row < $position) {
        $this->row++;
        continue;
      }
      $items[] = $row;
      $itemCount++;
      $this->row++;
      if ($itemCount === self::BATCHSIZE) {
        break;
      }
    }
    fclose($this->fileHandle);
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Create event, if any.
    if ($this->isEvent($item)) {
      // Create result, if any.
      $resultValues = $this->getValue($item, 'results');
      $result = !empty($item[array_search('results', self::CSVHEADERFORMAT)]) ? $this->importResult($resultValues, reset($resultValues), $this->group) : NULL;
      $resultId = !empty($result) ? $result->id() : NULL;
      // Create event.
      $this->latestEvent = $this->importEvent([
        $item[array_search('title', self::CSVHEADERFORMAT)],
        DateTime::createFromFormat('Y-m-d H:i', $item[array_search('start_date', self::CSVHEADERFORMAT)])->format(DATETIME_DATETIME_STORAGE_FORMAT),
        DateTime::createFromFormat('Y-m-d H:i', $item[array_search('end_date', self::CSVHEADERFORMAT)])->format(DATETIME_DATETIME_STORAGE_FORMAT),
        [
          'address' => $item[array_search('address', self::CSVHEADERFORMAT)],
          'extra_information' => $item[array_search('address_extra_information', self::CSVHEADERFORMAT)],
        ],
        $item[array_search('description', self::CSVHEADERFORMAT)],
        $resultId,
        $this->group->id(),
        NULL,
        $this->import->id(),
        NULL,
      ]);
    }
    // Otherwise, create and add extra entities.
    elseif (!empty($this->latestEvent)) {
      // Create result, if any.
      if (!empty($item[array_search('results', self::CSVHEADERFORMAT)]) && !empty($this->latestEvent)) {
        $resultValues = $this->getValue($item, 'results');
        $entity = $this->importResult($resultValues, reset($resultValues), $this->group);
        if ($entity) {
          // Attach to latest event.
          $this->latestEvent->results[] = [
            'target_id' => $entity->id(),
          ];
          $this->latestEvent->save();
        }
      }
    }
    return $this->latestEvent->id();
  }

  /**
   * Checks if row contains an event.
   *
   * @param array $row
   *   The row to check.
   *
   * @return bool
   *   Whether or not the row contains an event.
   */
  private function isEvent(array $row) {
    return !empty($row[array_search('start_date', self::CSVHEADERFORMAT)]);
  }

  /**
   * Returns trimmed values from the corresponding column.
   *
   * @param array $row
   *   The row to search value in.
   * @param string $columnName
   *   The column name.
   *
   * @return array
   *   Return values.
   */
  private function getValue(array $row, $columnName) {
    return array_map('trim', explode('|', $row[array_search($columnName, self::CSVHEADERFORMAT)]));
  }

  /**
   * Converts a column number to Excel-format column.
   *
   * @param int $column
   *   The column number to convert.
   *
   * @return string
   *   The corresponding column name.
   */
  private function convertColumn($column) {
    for ($name = ""; $column >= 0; $column = intval($column / 26) - 1) {
      $name = chr($column % 26 + 0x41) . $name;
    }
    return $name;
  }

}
