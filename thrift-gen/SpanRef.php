<?php
namespace Jaeger\Thrift;

/**
 * Autogenerated by Thrift Compiler (0.11.0)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


class SpanRef {
  static $isValidate = false;

  static $_TSPEC = array(
    1 => array(
      'var' => 'refType',
      'isRequired' => true,
      'type' => TType::I32,
      ),
    2 => array(
      'var' => 'traceIdLow',
      'isRequired' => true,
      'type' => TType::I64,
      ),
    3 => array(
      'var' => 'traceIdHigh',
      'isRequired' => true,
      'type' => TType::I64,
      ),
    4 => array(
      'var' => 'spanId',
      'isRequired' => true,
      'type' => TType::I64,
      ),
    );

  /**
   * @var int
   */
  public $refType = null;
  /**
   * @var int
   */
  public $traceIdLow = null;
  /**
   * @var int
   */
  public $traceIdHigh = null;
  /**
   * @var int
   */
  public $spanId = null;

  public function __construct($vals=null) {
    if (is_array($vals)) {
      if (isset($vals['refType'])) {
        $this->refType = $vals['refType'];
      }
      if (isset($vals['traceIdLow'])) {
        $this->traceIdLow = $vals['traceIdLow'];
      }
      if (isset($vals['traceIdHigh'])) {
        $this->traceIdHigh = $vals['traceIdHigh'];
      }
      if (isset($vals['spanId'])) {
        $this->spanId = $vals['spanId'];
      }
    }
  }

  public function getName() {
    return 'SpanRef';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->refType);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->traceIdLow);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->traceIdHigh);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->spanId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('SpanRef');
    if ($this->refType !== null) {
      $xfer += $output->writeFieldBegin('refType', TType::I32, 1);
      $xfer += $output->writeI32($this->refType);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->traceIdLow !== null) {
      $xfer += $output->writeFieldBegin('traceIdLow', TType::I64, 2);
      $xfer += $output->writeI64($this->traceIdLow);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->traceIdHigh !== null) {
      $xfer += $output->writeFieldBegin('traceIdHigh', TType::I64, 3);
      $xfer += $output->writeI64($this->traceIdHigh);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->spanId !== null) {
      $xfer += $output->writeFieldBegin('spanId', TType::I64, 4);
      $xfer += $output->writeI64($this->spanId);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

