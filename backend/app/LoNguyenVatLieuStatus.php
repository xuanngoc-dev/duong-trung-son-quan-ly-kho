<?php

namespace App;

enum LoNguyenVatLieuStatus: string
{
    case DaKiem = 'DA_KIEM';
    case Dat = 'DAT';
    case KhongDat = 'KHONG_DAT';
    case DacCach = 'DAC_CACH';
    case TraHang = 'TRA_HANG';
}
