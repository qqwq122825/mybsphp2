<?php

namespace App\Install;

enum ExistingDataAction: string
{
    case PreserveBusiness = 'preserve_business';
    case ResetAll = 'reset_all';
}
