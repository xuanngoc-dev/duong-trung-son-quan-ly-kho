<?php

namespace App;

enum TrangThaiTieuChuan: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Draft = 'Draft';
}
