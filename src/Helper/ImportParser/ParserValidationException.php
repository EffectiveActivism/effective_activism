<?php

namespace Drupal\effective_activism\Helper\ImportParser;

use Exception;

/**
 * Exception for parser validation errors.
 */
class ParserValidationException extends Exception {

  /**
   * Error code.
   *
   * @var int
   */
  private $errorCode;

  /**
   * Data line number.
   *
   * @var int
   */
  private $dataLine;

  /**
   * Data column number.
   *
   * @var int
   */
  private $dataColumn;

  /**
   * The offending data value, if any.
   *
   * @var string
   */
  private $value;

  /**
   * Any extra information can be stored in this variable.
   *
   * @var mixed
   */
  private $extrainformation;

  /**
   * Constructs a ParserValidationException.
   *
   * @param int $errorCode
   *   The exception error code.
   * @param int $line
   *   The line of the data file where the exception was thrown.
   * @param int $column
   *   The column of the data file where the exception was thrown.
   * @param string $value
   *   Any value involved with the error.
   * @param string $extra_information
   *   Any extra information to pass along with the error.
   */
  public function __construct($errorCode, $line = NULL, $column = NULL, $value = NULL, $extra_information = NULL) {
    $this->errorCode = $errorCode;
    $this->dataLine = $line;
    $this->dataColumn = $column;
    $this->value = $value;
    $this->extrainformation = $extra_information;
    parent::__construct();
  }

  /**
   * Returns the error code.
   *
   * @return int
   *   The error code.
   */
  public function getErrorCode() {
    return $this->errorCode;
  }

  /**
   * Returns the line of the data file where the exception was registered.
   *
   * @return int
   *   The line number.
   */
  public function getDataLine() {
    return $this->dataLine;
  }

  /**
   * Returns the column of the data file where the exception was registered.
   *
   * @return int
   *   The column number.
   */
  public function getDataColumn() {
    return $this->dataColumn;
  }

  /**
   * Returns the value of the data file where the exception was registered.
   *
   * @return string
   *   The value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Returns any extra information stored for this exception.
   *
   * @return string
   *   The value.
   */
  public function getExtraInformation() {
    return $this->extrainformation;
  }

}
