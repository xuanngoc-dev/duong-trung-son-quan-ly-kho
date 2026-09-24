import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'

const routes = [
  {
    path: '/',
    component: MainLayout,
    redirect: '/tong-quan',
    children: [
      {
        path: 'tong-quan',
        name: 'dashboard',
        component: () => import('@/views/tong-quan/TongQuan.vue'),
        meta: { title: 'Tổng quan' },
      },
      {
        path: 'danh-muc/hang-hoa',
        name: 'products',
        component: () => import('@/views/danh-muc/HangHoa.vue'),
        meta: { title: 'Hàng hóa' },
      },
      {
        path: 'kho/phieu-nhap',
        name: 'receipts',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Phiếu nhập kho',
          subtitle: 'Theo dõi chứng từ nhập hàng từ nhà cung cấp',
          action: 'Tạo phiếu nhập',
          type: 'receipt',
        },
      },
      {
        path: 'kho/phieu-xuat',
        name: 'issues',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Phiếu xuất kho',
          subtitle: 'Quản lý chứng từ xuất và bàn giao hàng hóa',
          action: 'Tạo phiếu xuất',
          type: 'issue',
        },
      },
      {
        path: 'kho/ton-kho',
        name: 'inventory',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Tồn kho',
          subtitle: 'Tra cứu số lượng hàng hóa theo kho và vị trí',
          action: 'Xuất báo cáo',
          type: 'inventory',
        },
      },
      {
        path: 'kho/kiem-ke',
        name: 'stocktake',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Kiểm kê kho',
          subtitle: 'Đối chiếu tồn hệ thống với số lượng thực tế',
          action: 'Tạo đợt kiểm kê',
          type: 'stocktake',
        },
      },
      {
        path: 'kho/kiem-tra-iqc',
        name: 'iqc',
        component: () => import('@/views/kho/kiemTraIqc.vue'),
        meta: {
          title: 'Kiểm tra IQC',
          subtitle: 'Phiếu kiểm tra chất lượng nguyên vật liệu đầu vào',
        },
      },
      {
        path: 'danh-muc/kho-vi-tri',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Kho & vị trí',
          subtitle: 'Thiết lập kho, khu vực, kệ và ô lưu trữ',
          action: 'Thêm kho',
          type: 'warehouse',
        },
      },
      {
        path: 'danh-muc/nha-cung-cap',
        component: () => import('@/views/danh-muc/nhaCungCap.vue'),
        meta: {
          title: 'Nhà cung cấp',
          subtitle: 'Quản lý thông tin và công nợ nhà cung cấp',
          action: 'Thêm nhà cung cấp',
          type: 'supplier',
        },
      },
      {
        path: 'danh-muc/nguyen-vat-lieu',
        name: 'materials',
        component: () => import('@/views/danh-muc/nguyenVatLieu.vue'),
        meta: {
          title: 'Nguyên vật liệu',
          subtitle: 'Danh mục nguyên vật liệu và quy cách kiểm tra',
        },
      },
      {
        path: 'danh-muc/tieu-chuan-kiem-tra',
        name: 'inspection-standards',
        component: () => import('@/views/danh-muc/tieuChuanKiemTra.vue'),
        meta: {
          title: 'Tiêu chuẩn kiểm tra',
          subtitle: 'Danh mục tiêu chuẩn và chỉ tiêu kiểm tra',
        },
      },
      {
        path: 'danh-muc/lo-nguyen-vat-lieu',
        name: 'material-lots',
        component: () => import('@/views/danh-muc/loNguyenVatLieu.vue'),
        meta: {
          title: 'Lô nguyên vật liệu',
          subtitle: 'Theo dõi lô nhập, số lượng và kết quả kiểm tra',
        },
      },
      {
        path: 'bao-cao/xuat-nhap-ton',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Báo cáo xuất nhập tồn',
          subtitle: 'Tổng hợp biến động hàng hóa theo thời gian',
          action: 'Xuất Excel',
          type: 'report',
        },
      },
      {
        path: 'he-thong/nhan-vien',
        name: 'employees',
        component: () => import('@/views/he-thong/quanLyNguoiDung.vue'),
        meta: {
          title: 'Nhân viên',
          subtitle: 'Quản lý tài khoản và thông tin nhân viên',
          action: 'Thêm nhân viên',
          type: 'employee',
        },
      },
      {
        path: 'he-thong/cau-hinh',
        component: () => import('@/views/shared/FeaturePage.vue'),
        meta: {
          title: 'Cấu hình hệ thống',
          subtitle: 'Thiết lập thông tin doanh nghiệp và quy tắc vận hành',
          action: 'Lưu cấu hình',
          type: 'settings',
        },
      },
    ],
  },
]

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})
