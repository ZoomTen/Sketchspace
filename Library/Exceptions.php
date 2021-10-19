<?php declare(strict_types = 1);

namespace Sketchspace\Exception;

use Exception;

class TooManyRequestsException extends Exception {}
class InvalidParameterException extends Exception {}
class RegisterException extends Exception{}
class MissingParametersException extends Exception{}
class ValidationError extends Exception{}