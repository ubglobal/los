-- ============================================================================
-- DEMO DATA for LOS v3.0
-- ============================================================================
-- This file contains comprehensive demo data for testing:
-- - 50+ customers (individuals + companies)
-- - 10 staff users (various roles)
-- - 100+ credit applications (diverse statuses)
-- - Facilities, disbursements, documents, collaterals
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 1. DEMO USERS (Staff)
-- ============================================================================
-- Password for all demo users: ub@12345678

INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `branch`, `approval_limit`) VALUES
-- CVQHKH - Các chi nhánh
('qhkh.an.nguyen', 'an.nguyen@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn An', 'CVQHKH', 'Hà Nội', NULL),
('qhkh.binh.le', 'binh.le@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Thị Bình', 'CVQHKH', 'Hồ Chí Minh', NULL),
('qhkh.cuong.tran', 'cuong.tran@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Văn Cường', 'CVQHKH', 'Đà Nẵng', NULL),

-- CVTĐ - Thẩm định
('thamdinh.lan.vu', 'lan.vu@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Vũ Thị Lan', 'CVTĐ', 'Hà Nội', NULL),
('thamdinh.minh.pham', 'minh.pham@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Phạm Văn Minh', 'CVTĐ', 'Hồ Chí Minh', NULL),

-- CPD - Phê duyệt (hạn mức <= 5 tỷ)
('pheduyet.hung.tran', 'hung.tran@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Văn Hùng', 'CPD', 'Hà Nội', 5000000000.00),
('pheduyet.nga.hoang', 'nga.hoang@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hoàng Thị Nga', 'CPD', 'Hồ Chí Minh', 5000000000.00),

-- GDK - Giám đốc khối (hạn mức > 5 tỷ)
('gd.khoi.nguyen', 'khoi.nguyen@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Khôi', 'GDK', 'Hội sở', 50000000000.00),

-- Kiểm soát
('kiemsoat.oanh.do', 'oanh.do@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Đỗ Thị Oanh', 'Kiểm soát', 'Hội sở', NULL),

-- Thủ quỹ
('thuquy.phong.nguyen', 'phong.nguyen@ubank.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Phong', 'Thủ quỹ', 'Hội sở', NULL);

-- ============================================================================
-- 2. DEMO CUSTOMERS - CÁ NHÂN (30 customers)
-- ============================================================================

INSERT INTO `customers` (`customer_code`, `customer_type`, `full_name`, `id_number`, `dob`, `address`, `phone_number`, `email`, `branch`) VALUES
('CN1001', 'CÁ NHÂN', 'Nguyễn Văn Anh', '001099001234', '1985-03-15', '123 Đường Láng, Đống Đa, Hà Nội', '0912345001', 'nv.anh@gmail.com', 'Hà Nội'),
('CN1002', 'CÁ NHÂN', 'Trần Thị Bình', '001099002345', '1990-07-22', '456 Nguyễn Huệ, Quận 1, TP.HCM', '0923456002', 'tt.binh@gmail.com', 'Hồ Chí Minh'),
('CN1003', 'CÁ NHÂN', 'Lê Văn Cường', '001099003456', '1988-11-30', '789 Lê Lợi, Hải Châu, Đà Nẵng', '0934567003', 'lv.cuong@gmail.com', 'Đà Nẵng'),
('CN1004', 'CÁ NHÂN', 'Phạm Thị Dung', '001099004567', '1992-05-18', '321 Trần Phú, Ba Đình, Hà Nội', '0945678004', 'pt.dung@gmail.com', 'Hà Nội'),
('CN1005', 'CÁ NHÂN', 'Hoàng Văn Em', '001099005678', '1987-09-25', '654 Pasteur, Quận 3, TP.HCM', '0956789005', 'hv.em@gmail.com', 'Hồ Chí Minh'),
('CN1006', 'CÁ NHÂN', 'Vũ Thị Phương', '001099006789', '1995-01-12', '987 Ngô Quyền, Hải Châu, Đà Nẵng', '0967890006', 'vt.phuong@gmail.com', 'Đà Nẵng'),
('CN1007', 'CÁ NHÂN', 'Đặng Văn Giang', '001099007890', '1983-06-08', '147 Hoàng Hoa Thám, Tây Hồ, Hà Nội', '0978901007', 'dv.giang@gmail.com', 'Hà Nội'),
('CN1008', 'CÁ NHÂN', 'Bùi Thị Hằng', '001099008901', '1991-12-20', '258 Võ Văn Tần, Quận 3, TP.HCM', '0989012008', 'bt.hang@gmail.com', 'Hồ Chí Minh'),
('CN1009', 'CÁ NHÂN', 'Ngô Văn Huy', '001099009012', '1986-04-14', '369 Hùng Vương, Hải Châu, Đà Nẵng', '0990123009', 'nv.huy@gmail.com', 'Đà Nẵng'),
('CN1010', 'CÁ NHÂN', 'Đinh Thị Hoa', '001099010123', '1993-08-27', '741 Giải Phóng, Hoàng Mai, Hà Nội', '0901234010', 'dt.hoa@gmail.com', 'Hà Nội'),
('CN1011', 'CÁ NHÂN', 'Phan Văn Khải', '001099011234', '1989-02-16', '852 Lý Thường Kiệt, Quận 10, TP.HCM', '0912345011', 'pv.khai@gmail.com', 'Hồ Chí Minh'),
('CN1012', 'CÁ NHÂN', 'Trương Thị Lan', '001099012345', '1994-10-03', '963 Trần Hưng Đạo, Hải Châu, Đà Nẵng', '0923456012', 'tt.lan@gmail.com', 'Đà Nẵng'),
('CN1013', 'CÁ NHÂN', 'Dương Văn Minh', '001099013456', '1984-07-19', '159 Nguyễn Trãi, Thanh Xuân, Hà Nội', '0934567013', 'dv.minh@gmail.com', 'Hà Nội'),
('CN1014', 'CÁ NHÂN', 'Mai Thị Nga', '001099014567', '1996-03-11', '357 Cách Mạng Tháng 8, Quận 3, TP.HCM', '0945678014', 'mt.nga@gmail.com', 'Hồ Chí Minh'),
('CN1015', 'CÁ NHÂN', 'Lý Văn Oanh', '001099015678', '1990-11-28', '753 Bạch Đằng, Hải Châu, Đà Nẵng', '0956789015', 'lv.oanh@gmail.com', 'Đà Nẵng'),
('CN1016', 'CÁ NHÂN', 'Võ Thị Phúc', '001099016789', '1988-05-05', '951 Kim Mã, Ba Đình, Hà Nội', '0967890016', 'vt.phuc@gmail.com', 'Hà Nội'),
('CN1017', 'CÁ NHÂN', 'Hồ Văn Quang', '001099017890', '1992-09-14', '246 Nam Kỳ Khởi Nghĩa, Quận 1, TP.HCM', '0978901017', 'hv.quang@gmail.com', 'Hồ Chí Minh'),
('CN1018', 'CÁ NHÂN', 'Cao Thị Trang', '001099018901', '1987-01-22', '468 Điện Biên Phủ, Hải Châu, Đà Nẵng', '0989012018', 'ct.trang@gmail.com', 'Đà Nẵng'),
('CN1019', 'CÁ NHÂN', 'Tô Văn Thái', '001099019012', '1995-06-09', '579 Láng Hạ, Đống Đa, Hà Nội', '0990123019', 'tv.thai@gmail.com', 'Hà Nội'),
('CN1020', 'CÁ NHÂN', 'Đỗ Thị Uyên', '001099020123', '1991-12-17', '680 Hai Bà Trưng, Quận 1, TP.HCM', '0901234020', 'dt.uyen@gmail.com', 'Hồ Chí Minh'),
('CN1021', 'CÁ NHÂN', 'Lưu Văn Vũ', '001099021234', '1986-04-26', '791 Núi Thành, Hải Châu, Đà Nẵng', '0912345021', 'lv.vu@gmail.com', 'Đà Nẵng'),
('CN1022', 'CÁ NHÂN', 'Phan Thị Xuân', '001099022345', '1993-08-13', '802 Thái Hà, Đống Đa, Hà Nội', '0923456022', 'pt.xuan@gmail.com', 'Hà Nội'),
('CN1023', 'CÁ NHÂN', 'Chu Văn Yên', '001099023456', '1989-02-28', '913 Nguyễn Đình Chiểu, Quận 3, TP.HCM', '0934567023', 'cv.yen@gmail.com', 'Hồ Chí Minh'),
('CN1024', 'CÁ NHÂN', 'Tạ Thị Bảo', '001099024567', '1994-10-15', '024 Hoàng Diệu, Hải Châu, Đà Nẵng', '0945678024', 'tt.bao@gmail.com', 'Đà Nẵng'),
('CN1025', 'CÁ NHÂN', 'Quách Văn Chiến', '001099025678', '1985-07-02', '135 Nguyễn Chí Thanh, Đống Đa, Hà Nội', '0956789025', 'qv.chien@gmail.com', 'Hà Nội'),
('CN1026', 'CÁ NHÂN', 'Tăng Thị Diễm', '001099026789', '1990-03-20', '246 Lê Văn Sỹ, Quận 3, TP.HCM', '0967890026', 'tt.diem@gmail.com', 'Hồ Chí Minh'),
('CN1027', 'CÁ NHÂN', 'Trịnh Văn Hải', '001099027890', '1988-11-07', '357 Nguyễn Văn Linh, Hải Châu, Đà Nẵng', '0978901027', 'tv.hai@gmail.com', 'Đà Nẵng'),
('CN1028', 'CÁ NHÂN', 'Ông Thị Khánh', '001099028901', '1992-05-24', '468 Hoàng Quốc Việt, Cầu Giấy, Hà Nội', '0989012028', 'ot.khanh@gmail.com', 'Hà Nội'),
('CN1029', 'CÁ NHÂN', 'Uông Văn Linh', '001099029012', '1987-09-11', '579 Phan Xích Long, Phú Nhuận, TP.HCM', '0990123029', 'uv.linh@gmail.com', 'Hồ Chí Minh'),
('CN1030', 'CÁ NHÂN', 'Dư Thị Mai', '001099030123', '1995-01-18', '680 Trường Chinh, Thanh Khê, Đà Nẵng', '0901234030', 'dt.mai@gmail.com', 'Đà Nẵng');

-- ============================================================================
-- 3. DEMO CUSTOMERS - DOANH NGHIỆP (20 companies)
-- ============================================================================

INSERT INTO `customers` (`customer_code`, `customer_type`, `full_name`, `company_tax_code`, `company_representative`, `address`, `phone_number`, `email`, `branch`) VALUES
('DN2001', 'DOANH NGHIỆP', 'Công ty TNHH Thương Mại ABC', '0123456789', 'Nguyễn Văn Nam', '12 Phạm Ngũ Lão, Hoàn Kiếm, Hà Nội', '0241234001', 'contact@abc.com.vn', 'Hà Nội'),
('DN2002', 'DOANH NGHIỆP', 'Công ty Cổ phần Đầu Tư XYZ', '0234567890', 'Trần Thị Oanh', '34 Nguyễn Huệ, Quận 1, TP.HCM', '0282345002', 'info@xyz.vn', 'Hồ Chí Minh'),
('DN2003', 'DOANH NGHIỆP', 'Công ty TNHH Sản Xuất Minh Phát', '0345678901', 'Lê Văn Phúc', '56 Hùng Vương, Hải Châu, Đà Nẵng', '0263456003', 'minhphat@company.vn', 'Đà Nẵng'),
('DN2004', 'DOANH NGHIỆP', 'Công ty Cổ phần Xuất Nhập Khẩu Hòa Bình', '0456789012', 'Phạm Thị Quỳnh', '78 Láng Hạ, Đống Đa, Hà Nội', '0244567004', 'hoabinh@export.vn', 'Hà Nội'),
('DN2005', 'DOANH NGHIỆP', 'Công ty TNHH Vận Tải Thành Đạt', '0567890123', 'Hoàng Văn Tùng', '90 Lê Lợi, Quận 1, TP.HCM', '0285678005', 'thanhdat@logistics.vn', 'Hồ Chí Minh'),
('DN2006', 'DOANH NGHIỆP', 'Công ty Cổ phần Công Nghệ Số 1', '0678901234', 'Vũ Thị Uyên', '12 Điện Biên Phủ, Hải Châu, Đà Nẵng', '0266789006', 'tech1@digital.vn', 'Đà Nẵng'),
('DN2007', 'DOANH NGHIỆP', 'Công ty TNHH Xây Dựng An Phát', '0789012345', 'Đặng Văn Vinh', '34 Giải Phóng, Hoàng Mai, Hà Nội', '0247890007', 'anphat@construction.vn', 'Hà Nội'),
('DN2008', 'DOANH NGHIỆP', 'Công ty Cổ phần Dược Phẩm Sài Gòn', '0890123456', 'Bùi Thị Xuân', '56 Pasteur, Quận 1, TP.HCM', '0288901008', 'saigonpharma@healthcare.vn', 'Hồ Chí Minh'),
('DN2009', 'DOANH NGHIỆP', 'Công ty TNHH Thực Phẩm Tươi Ngon', '0901234567', 'Ngô Văn Yên', '78 Trần Phú, Hải Châu, Đà Nẵng', '0269012009', 'freshfood@food.vn', 'Đà Nẵng'),
('DN2010', 'DOANH NGHIỆP', 'Công ty Cổ phần May Mặc Việt Nam', '1012345678', 'Đinh Thị Bảo', '90 Nguyễn Trãi, Thanh Xuân, Hà Nội', '0240123010', 'vietnam@garment.vn', 'Hà Nội'),
('DN2011', 'DOANH NGHIỆP', 'Công ty TNHH Điện Tử Thông Minh', '1123456789', 'Phan Văn Chiến', '12 Võ Văn Tần, Quận 3, TP.HCM', '0281234011', 'smart@electronics.vn', 'Hồ Chí Minh'),
('DN2012', 'DOANH NGHIỆP', 'Công ty Cổ phần Du Lịch Biển Xanh', '1234567890', 'Trương Thị Diễm', '34 Bạch Đằng, Hải Châu, Đà Nẵng', '0262345012', 'bluetravel@tourism.vn', 'Đà Nẵng'),
('DN2013', 'DOANH NGHIỆP', 'Công ty TNHH Nội Thất Hiện Đại', '2345678901', 'Dương Văn Hải', '56 Kim Mã, Ba Đình, Hà Nội', '0243456013', 'modern@furniture.vn', 'Hà Nội'),
('DN2014', 'DOANH NGHIỆP', 'Công ty Cổ phần Bất Động Sản Thịnh Vượng', '3456789012', 'Mai Thị Khánh', '78 Nam Kỳ Khởi Nghĩa, Quận 1, TP.HCM', '0284567014', 'thinhvuong@realestate.vn', 'Hồ Chí Minh'),
('DN2015', 'DOANH NGHIỆP', 'Công ty TNHH Cơ Khí Chính Xác', '4567890123', 'Lý Văn Linh', '90 Núi Thành, Hải Châu, Đà Nẵng', '0265678015', 'precision@mechanical.vn', 'Đà Nẵng'),
('DN2016', 'DOANH NGHIỆP', 'Công ty Cổ phần Nông Nghiệp Xanh', '5678901234', 'Võ Thị Mai', '12 Thái Hà, Đống Đa, Hà Nội', '0246789016', 'green@agriculture.vn', 'Hà Nội'),
('DN2017', 'DOANH NGHIỆP', 'Công ty TNHH Hóa Chất Công Nghiệp', '6789012345', 'Hồ Văn Nam', '34 Nguyễn Đình Chiểu, Quận 3, TP.HCM', '0287890017', 'industrial@chemical.vn', 'Hồ Chí Minh'),
('DN2018', 'DOANH NGHIỆP', 'Công ty Cổ phần Giáo Dục Tương Lai', '7890123456', 'Cao Thị Oanh', '56 Hoàng Diệu, Hải Châu, Đà Nẵng', '0268901018', 'future@education.vn', 'Đà Nẵng'),
('DN2019', 'DOANH NGHIỆP', 'Công ty TNHH Vật Liệu Xây Dựng Đại Phát', '8901234567', 'Tô Văn Phúc', '78 Nguyễn Chí Thanh, Đống Đa, Hà Nội', '0249012019', 'daiphat@materials.vn', 'Hà Nội'),
('DN2020', 'DOANH NGHIỆP', 'Công ty Cổ phần Năng Lượng Tái Tạo', '9012345678', 'Đỗ Thị Quỳnh', '90 Lê Văn Sỹ, Quận 3, TP.HCM', '0280123020', 'renewable@energy.vn', 'Hồ Chí Minh');

-- ============================================================================
-- 4. CREDIT APPLICATIONS (110 applications - diverse statuses)
-- ============================================================================
-- Distribution:
-- - 15 Bản nháp (draft)
-- - 25 Đang xử lý (in progress)
-- - 40 Đã phê duyệt (approved - will have facilities)
-- - 15 Từ chối (rejected)
-- - 10 Yêu cầu bổ sung (need more info)
-- - 5 Đã hủy (cancelled)
-- ============================================================================

-- Bản nháp (Draft Applications) - 15 records
INSERT INTO `credit_applications`
(`hstd_code`, `customer_id`, `product_id`, `amount`, `term_months`, `purpose`, `status`, `stage`, `created_by_id`, `created_at`, `sla_target_date`, `sla_status`) VALUES
('HSTD2024100001', 1, 1, 150000000.00, 12, 'Bổ sung vốn lưu động kinh doanh', 'Bản nháp', 'Khởi tạo', 2, '2024-10-28 09:15:00', '2024-11-11 17:00:00', 'On Track'),
('HSTD2024100002', 5, 2, 500000000.00, 36, 'Mua sắm thiết bị sản xuất', 'Bản nháp', 'Khởi tạo', 3, '2024-10-28 10:30:00', '2024-11-11 17:00:00', 'On Track'),
('HSTD2024100003', 10, 1, 200000000.00, 12, 'Vốn lưu động mua hàng hóa', 'Bản nháp', 'Khởi tạo', 2, '2024-10-29 08:45:00', '2024-11-12 17:00:00', 'On Track'),
('HSTD2024100004', 15, 3, 300000000.00, 6, 'Tài trợ xuất khẩu hàng hóa', 'Bản nháp', 'Khởi tạo', 4, '2024-10-29 14:20:00', '2024-11-12 17:00:00', 'On Track'),
('HSTD2024100005', 20, 1, 180000000.00, 12, 'Vốn kinh doanh nhà hàng', 'Bản nháp', 'Khởi tạo', 3, '2024-10-29 16:00:00', '2024-11-12 17:00:00', 'On Track'),
('HSTD2024100006', 31, 2, 3000000000.00, 48, 'Đầu tư mở rộng nhà máy', 'Bản nháp', 'Khởi tạo', 2, '2024-10-30 09:00:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100007', 35, 1, 2500000000.00, 12, 'Vốn lưu động mua nguyên liệu', 'Bản nháp', 'Khởi tạo', 3, '2024-10-30 10:15:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100008', 12, 1, 120000000.00, 12, 'Vốn kinh doanh cửa hàng', 'Bản nháp', 'Khởi tạo', 2, '2024-10-30 11:30:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100009', 18, 2, 400000000.00, 24, 'Mua xe ô tô phục vụ kinh doanh', 'Bản nháp', 'Khởi tạo', 4, '2024-10-30 13:45:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100010', 25, 1, 250000000.00, 12, 'Vốn kinh doanh thương mại', 'Bản nháp', 'Khởi tạo', 3, '2024-10-30 15:00:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100011', 40, 3, 1500000000.00, 6, 'Thanh toán L/C nhập khẩu', 'Bản nháp', 'Khởi tạo', 2, '2024-10-30 16:20:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100012', 8, 1, 90000000.00, 12, 'Vốn lưu động kinh doanh cá nhân', 'Bản nháp', 'Khởi tạo', 3, '2024-10-30 17:00:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100013', 42, 4, 5000000000.00, 12, 'Hạn mức thấu chi tài khoản', 'Bản nháp', 'Khởi tạo', 2, '2024-10-30 17:30:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100014', 28, 1, 170000000.00, 12, 'Bổ sung vốn kinh doanh', 'Bản nháp', 'Khởi tạo', 4, '2024-10-30 18:00:00', '2024-11-13 17:00:00', 'On Track'),
('HSTD2024100015', 45, 2, 8000000000.00, 60, 'Xây dựng nhà xưởng mới', 'Bản nháp', 'Khởi tạo', 3, '2024-10-30 18:30:00', '2024-11-13 17:00:00', 'On Track');

-- Đang xử lý (In Progress) - 25 records with various stages
INSERT INTO `credit_applications`
(`hstd_code`, `customer_id`, `product_id`, `amount`, `term_months`, `purpose`, `status`, `stage`, `created_by_id`, `assigned_to_id`, `reviewed_by_id`, `created_at`, `updated_at`, `sla_target_date`, `sla_status`) VALUES
('HSTD2024090016', 2, 1, 300000000.00, 12, 'Vốn lưu động kinh doanh', 'Đang xử lý', 'Thu thập hồ sơ', 2, 2, NULL, '2024-09-15 09:00:00', '2024-09-16 10:30:00', '2024-09-29 17:00:00', 'Warning'),
('HSTD2024090017', 32, 2, 5000000000.00, 36, 'Đầu tư máy móc sản xuất', 'Đang xử lý', 'Thẩm định', 2, 5, 5, '2024-09-20 10:00:00', '2024-10-05 14:00:00', '2024-10-04 17:00:00', 'Overdue'),
('HSTD2024090018', 7, 1, 250000000.00, 12, 'Vốn mua hàng hóa', 'Đang xử lý', 'Thu thập hồ sơ', 3, 3, NULL, '2024-09-22 11:00:00', '2024-09-25 09:00:00', '2024-10-06 17:00:00', 'On Track'),
('HSTD2024090019', 38, 3, 2000000000.00, 6, 'Tài trợ xuất khẩu', 'Đang xử lý', 'Phê duyệt', 2, 7, 5, '2024-09-25 08:30:00', '2024-10-10 16:00:00', '2024-10-09 17:00:00', 'Overdue'),
('HSTD2024090020', 14, 1, 180000000.00, 12, 'Vốn lưu động', 'Đang xử lý', 'Thẩm định', 4, 6, 6, '2024-09-28 09:15:00', '2024-10-08 11:00:00', '2024-10-12 17:00:00', 'On Track'),
('HSTD2024090021', 33, 1, 4000000000.00, 12, 'Vốn lưu động doanh nghiệp', 'Đang xử lý', 'Phê duyệt', 3, 7, 6, '2024-10-01 10:00:00', '2024-10-15 14:30:00', '2024-10-15 17:00:00', 'On Track'),
('HSTD2024090022', 22, 2, 600000000.00, 24, 'Mua xe ô tô kinh doanh', 'Đang xử lý', 'Thu thập hồ sơ', 2, 2, NULL, '2024-10-03 11:30:00', '2024-10-08 10:00:00', '2024-10-17 17:00:00', 'On Track'),
('HSTD2024090023', 41, 2, 10000000000.00, 48, 'Xây dựng cơ sở hạ tầng', 'Đang xử lý', 'Thẩm định', 3, 5, 5, '2024-10-05 08:45:00', '2024-10-20 15:00:00', '2024-10-19 17:00:00', 'Overdue'),
('HSTD2024090024', 11, 1, 220000000.00, 12, 'Vốn kinh doanh nhà hàng', 'Đang xử lý', 'Thu thập hồ sơ', 4, 4, NULL, '2024-10-08 09:00:00', '2024-10-12 11:00:00', '2024-10-22 17:00:00', 'On Track'),
('HSTD2024090025', 36, 1, 3500000000.00, 12, 'Vốn lưu động sản xuất', 'Đang xử lý', 'Phê duyệt', 2, 7, 5, '2024-10-10 10:15:00', '2024-10-25 14:00:00', '2024-10-24 17:00:00', 'Overdue'),
('HSTD2024090026', 17, 1, 280000000.00, 12, 'Vốn kinh doanh', 'Đang xử lý', 'Thẩm định', 3, 6, 6, '2024-10-12 11:00:00', '2024-10-22 10:00:00', '2024-10-26 17:00:00', 'On Track'),
('HSTD2024090027', 44, 3, 3000000000.00, 6, 'Thanh toán L/C', 'Đang xử lý', 'Phê duyệt', 2, 8, 6, '2024-10-14 09:30:00', '2024-10-28 16:00:00', '2024-10-28 17:00:00', 'On Track'),
('HSTD2024090028', 9, 1, 150000000.00, 12, 'Vốn lưu động cửa hàng', 'Đang xử lý', 'Thu thập hồ sơ', 4, 4, NULL, '2024-10-16 10:00:00', '2024-10-20 09:00:00', '2024-10-30 17:00:00', 'On Track'),
('HSTD2024090029', 37, 2, 7000000000.00, 60, 'Đầu tư dây chuyền sản xuất', 'Đang xử lý', 'Thẩm định', 3, 5, 5, '2024-10-18 11:15:00', '2024-10-28 14:00:00', '2024-11-01 17:00:00', 'On Track'),
('HSTD2024090030', 24, 1, 320000000.00, 12, 'Vốn kinh doanh thương mại', 'Đang xử lý', 'Thu thập hồ sơ', 2, 2, NULL, '2024-10-20 08:30:00', '2024-10-25 10:00:00', '2024-11-03 17:00:00', 'On Track'),
('HSTD2024090031', 39, 4, 8000000000.00, 12, 'Hạn mức thấu chi', 'Đang xử lý', 'Phê duyệt', 2, 9, 5, '2024-10-21 09:00:00', '2024-10-29 15:00:00', '2024-11-04 17:00:00', 'On Track'),
('HSTD2024090032', 13, 1, 190000000.00, 12, 'Vốn lưu động', 'Đang xử lý', 'Thẩm định', 4, 6, 6, '2024-10-22 10:30:00', '2024-10-28 11:00:00', '2024-11-05 17:00:00', 'On Track'),
('HSTD2024090033', 34, 2, 6000000000.00, 48, 'Mua sắm tài sản cố định', 'Đang xử lý', 'Phê duyệt', 3, 7, 5, '2024-10-23 11:00:00', '2024-10-29 14:00:00', '2024-11-06 17:00:00', 'On Track'),
('HSTD2024090034', 19, 1, 270000000.00, 12, 'Vốn kinh doanh', 'Đang xử lý', 'Thu thập hồ sơ', 2, 2, NULL, '2024-10-24 09:15:00', '2024-10-28 10:00:00', '2024-11-07 17:00:00', 'On Track'),
('HSTD2024090035', 43, 3, 2500000000.00, 6, 'Tài trợ nhập khẩu', 'Đang xử lý', 'Thẩm định', 3, 5, 5, '2024-10-25 10:00:00', '2024-10-29 15:00:00', '2024-11-08 17:00:00', 'On Track'),
('HSTD2024090036', 6, 1, 140000000.00, 12, 'Vốn lưu động kinh doanh', 'Đang xử lý', 'Thu thập hồ sơ', 4, 4, NULL, '2024-10-26 11:30:00', '2024-10-29 09:00:00', '2024-11-09 17:00:00', 'On Track'),
('HSTD2024090037', 46, 2, 12000000000.00, 60, 'Đầu tư xây dựng nhà máy', 'Đang xử lý', 'Phê duyệt', 2, 9, 5, '2024-10-27 08:45:00', '2024-10-30 16:00:00', '2024-11-10 17:00:00', 'On Track'),
('HSTD2024090038', 16, 1, 230000000.00, 12, 'Vốn kinh doanh', 'Đang xử lý', 'Thẩm định', 3, 6, 6, '2024-10-27 10:00:00', '2024-10-30 14:00:00', '2024-11-10 17:00:00', 'On Track'),
('HSTD2024090039', 47, 1, 4500000000.00, 12, 'Vốn lưu động DN', 'Đang xử lý', 'Phê duyệt', 2, 7, 5, '2024-10-28 09:30:00', '2024-10-30 15:00:00', '2024-11-11 17:00:00', 'On Track'),
('HSTD2024090040', 21, 2, 550000000.00, 36, 'Đầu tư thiết bị', 'Đang xử lý', 'Thu thập hồ sơ', 4, 4, NULL, '2024-10-28 11:00:00', '2024-10-30 10:00:00', '2024-11-11 17:00:00', 'On Track');

-- Đã phê duyệt (Approved Applications) - 40 records
-- These will have facilities created
INSERT INTO `credit_applications`
(`hstd_code`, `customer_id`, `product_id`, `amount`, `term_months`, `purpose`, `status`, `stage`, `created_by_id`, `assigned_to_id`, `reviewed_by_id`, `approved_by_id`, `created_at`, `updated_at`, `sla_target_date`, `sla_status`) VALUES
('HSTD2024080041', 3, 1, 350000000.00, 12, 'Vốn lưu động kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 7, '2024-08-01 09:00:00', '2024-08-15 16:00:00', '2024-08-15 17:00:00', 'On Track'),
('HSTD2024080042', 31, 2, 6000000000.00, 48, 'Đầu tư mở rộng sản xuất', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-08-05 10:00:00', '2024-08-20 15:00:00', '2024-08-19 17:00:00', 'On Track'),
('HSTD2024080043', 4, 1, 280000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 6, 7, '2024-08-08 11:00:00', '2024-08-22 14:00:00', '2024-08-22 17:00:00', 'On Track'),
('HSTD2024080044', 35, 3, 3500000000.00, 6, 'Tài trợ xuất nhập khẩu', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 5, 8, '2024-08-10 09:30:00', '2024-08-24 16:00:00', '2024-08-24 17:00:00', 'On Track'),
('HSTD2024080045', 23, 1, 320000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 6, 7, '2024-08-12 10:15:00', '2024-08-26 15:00:00', '2024-08-26 17:00:00', 'On Track'),
('HSTD2024080046', 38, 4, 10000000000.00, 12, 'Hạn mức thấu chi', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 9, '2024-08-15 11:00:00', '2024-08-29 14:00:00', '2024-08-29 17:00:00', 'On Track'),
('HSTD2024080047', 26, 2, 700000000.00, 36, 'Mua sắm tài sản', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 7, '2024-08-17 09:45:00', '2024-08-31 16:00:00', '2024-08-31 17:00:00', 'On Track'),
('HSTD2024080048', 41, 1, 5500000000.00, 12, 'Vốn lưu động DN', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-08-19 10:30:00', '2024-09-02 15:00:00', '2024-09-02 17:00:00', 'On Track'),
('HSTD2024080049', 27, 1, 390000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 6, 7, '2024-08-21 11:15:00', '2024-09-04 14:00:00', '2024-09-04 17:00:00', 'On Track'),
('HSTD2024080050', 43, 2, 8500000000.00, 60, 'Xây dựng nhà máy', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 5, 9, '2024-08-23 09:00:00', '2024-09-06 16:00:00', '2024-09-06 17:00:00', 'On Track'),
('HSTD2024080051', 29, 1, 260000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 6, 7, '2024-08-25 10:00:00', '2024-09-08 15:00:00', '2024-09-08 17:00:00', 'On Track'),
('HSTD2024080052', 34, 3, 2800000000.00, 6, 'Tài trợ thương mại', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 8, '2024-08-27 11:30:00', '2024-09-10 14:00:00', '2024-09-10 17:00:00', 'On Track'),
('HSTD2024080053', 30, 1, 410000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 7, '2024-08-29 09:15:00', '2024-09-12 16:00:00', '2024-09-12 17:00:00', 'On Track'),
('HSTD2024080054', 42, 2, 9000000000.00, 48, 'Đầu tư CSHT', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-08-31 10:00:00', '2024-09-14 15:00:00', '2024-09-14 17:00:00', 'On Track'),
('HSTD2024080055', 48, 1, 4200000000.00, 12, 'Vốn lưu động DN', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 7, '2024-09-02 11:00:00', '2024-09-16 14:00:00', '2024-09-16 17:00:00', 'On Track'),
('HSTD2024080056', 49, 3, 3200000000.00, 6, 'Tài trợ xuất khẩu', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 8, '2024-09-04 09:30:00', '2024-09-18 16:00:00', '2024-09-18 17:00:00', 'On Track'),
('HSTD2024080057', 50, 4, 7500000000.00, 12, 'Thấu chi tài khoản', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-09-06 10:15:00', '2024-09-20 15:00:00', '2024-09-20 17:00:00', 'On Track'),
('HSTD2024080058', 44, 2, 11000000000.00, 60, 'Đầu tư dự án lớn', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 9, '2024-09-08 11:00:00', '2024-09-22 14:00:00', '2024-09-22 17:00:00', 'On Track'),
('HSTD2024080059', 45, 1, 6200000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 9, '2024-09-10 09:45:00', '2024-09-24 16:00:00', '2024-09-24 17:00:00', 'On Track'),
('HSTD2024080060', 46, 2, 9500000000.00, 48, 'Mua sắm máy móc', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-09-11 10:30:00', '2024-09-25 15:00:00', '2024-09-25 17:00:00', 'On Track'),
('HSTD2024070061', 1, 1, 160000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 6, 7, '2024-07-01 09:00:00', '2024-07-15 16:00:00', '2024-07-15 17:00:00', 'On Track'),
('HSTD2024070062', 32, 1, 4800000000.00, 12, 'Vốn lưu động DN', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 7, '2024-07-03 10:00:00', '2024-07-17 15:00:00', '2024-07-17 17:00:00', 'On Track'),
('HSTD2024070063', 5, 2, 520000000.00, 36, 'Đầu tư thiết bị', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 7, '2024-07-05 11:00:00', '2024-07-19 14:00:00', '2024-07-19 17:00:00', 'On Track'),
('HSTD2024070064', 36, 3, 2600000000.00, 6, 'Tài trợ nhập khẩu', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 8, '2024-07-07 09:30:00', '2024-07-21 16:00:00', '2024-07-21 17:00:00', 'On Track'),
('HSTD2024070065', 7, 1, 240000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 6, 7, '2024-07-09 10:15:00', '2024-07-23 15:00:00', '2024-07-23 17:00:00', 'On Track'),
('HSTD2024070066', 39, 2, 7800000000.00, 48, 'Đầu tư mở rộng', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 5, 9, '2024-07-11 11:00:00', '2024-07-25 14:00:00', '2024-07-25 17:00:00', 'On Track'),
('HSTD2024070067', 9, 1, 195000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 6, 7, '2024-07-13 09:45:00', '2024-07-27 16:00:00', '2024-07-27 17:00:00', 'On Track'),
('HSTD2024070068', 40, 4, 6500000000.00, 12, 'Hạn mức thấu chi', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-07-15 10:30:00', '2024-07-29 15:00:00', '2024-07-29 17:00:00', 'On Track'),
('HSTD2024070069', 11, 1, 215000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 7, '2024-07-17 11:15:00', '2024-07-31 14:00:00', '2024-07-31 17:00:00', 'On Track'),
('HSTD2024070070', 47, 2, 8200000000.00, 60, 'Xây dựng nhà máy', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 9, '2024-07-19 09:00:00', '2024-08-02 16:00:00', '2024-08-02 17:00:00', 'On Track'),
('HSTD2024060071', 13, 1, 275000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 6, 7, '2024-06-01 10:00:00', '2024-06-15 15:00:00', '2024-06-15 17:00:00', 'On Track'),
('HSTD2024060072', 33, 1, 3900000000.00, 12, 'Vốn lưu động DN', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 5, 7, '2024-06-05 11:00:00', '2024-06-19 14:00:00', '2024-06-19 17:00:00', 'On Track'),
('HSTD2024060073', 15, 2, 580000000.00, 24, 'Mua xe ô tô', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 6, 7, '2024-06-10 09:30:00', '2024-06-24 16:00:00', '2024-06-24 17:00:00', 'On Track'),
('HSTD2024060074', 37, 3, 2900000000.00, 6, 'Tài trợ thương mại', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 8, '2024-06-15 10:15:00', '2024-06-29 15:00:00', '2024-06-29 17:00:00', 'On Track'),
('HSTD2024060075', 17, 1, 335000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 6, 7, '2024-06-20 11:00:00', '2024-07-04 14:00:00', '2024-07-04 17:00:00', 'On Track'),
('HSTD2024060076', 48, 2, 10500000000.00, 60, 'Đầu tư dự án', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 5, 9, '2024-06-25 09:45:00', '2024-07-09 16:00:00', '2024-07-09 17:00:00', 'On Track'),
('HSTD2024050077', 19, 1, 290000000.00, 12, 'Vốn lưu động', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 6, 7, '2024-05-05 10:00:00', '2024-05-19 15:00:00', '2024-05-19 17:00:00', 'On Track'),
('HSTD2024050078', 49, 3, 3100000000.00, 6, 'Tài trợ xuất khẩu', 'Đã phê duyệt', 'Hoàn tất', 4, 4, 5, 8, '2024-05-10 11:00:00', '2024-05-24 14:00:00', '2024-05-24 17:00:00', 'On Track'),
('HSTD2024050079', 22, 1, 355000000.00, 12, 'Vốn kinh doanh', 'Đã phê duyệt', 'Hoàn tất', 2, 2, 6, 7, '2024-05-15 09:30:00', '2024-05-29 16:00:00', '2024-05-29 17:00:00', 'On Track'),
('HSTD2024050080', 50, 4, 9200000000.00, 12, 'Hạn mức thấu chi', 'Đã phê duyệt', 'Hoàn tất', 3, 3, 5, 9, '2024-05-20 10:15:00', '2024-06-03 15:00:00', '2024-06-03 17:00:00', 'On Track');

-- Từ chối (Rejected Applications) - 15 records
INSERT INTO `credit_applications`
(`hstd_code`, `customer_id`, `product_id`, `amount`, `term_months`, `purpose`, `status`, `stage`, `created_by_id`, `assigned_to_id`, `reviewed_by_id`, `approved_by_id`, `rejection_reason`, `created_at`, `updated_at`, `sla_target_date`, `sla_status`) VALUES
('HSTD2024090081', 6, 2, 800000000.00, 48, 'Đầu tư thiết bị', 'Từ chối', 'Đã kết thúc', 2, 5, 5, 7, 'Không đủ khả năng tài chính, thu nhập không ổn định', '2024-09-05 09:00:00', '2024-09-18 16:00:00', '2024-09-19 17:00:00', 'On Track'),
('HSTD2024090082', 8, 1, 150000000.00, 12, 'Vốn lưu động', 'Từ chối', 'Đã kết thúc', 3, 6, 6, 7, 'Lịch sử tín dụng không tốt tại ngân hàng khác', '2024-09-08 10:00:00', '2024-09-20 15:00:00', '2024-09-22 17:00:00', 'On Track'),
('HSTD2024090083', 12, 1, 100000000.00, 12, 'Vốn kinh doanh', 'Từ chối', 'Đã kết thúc', 4, 5, 5, 7, 'Không có tài sản đảm bảo, mục đích vay không rõ ràng', '2024-09-10 11:00:00', '2024-09-22 14:00:00', '2024-09-24 17:00:00', 'On Track'),
('HSTD2024090084', 14, 2, 600000000.00, 36, 'Mua xe', 'Từ chối', 'Đã kết thúc', 2, 6, 6, 7, 'Tỷ lệ nợ/thu nhập vượt quy định', '2024-09-12 09:30:00', '2024-09-24 16:00:00', '2024-09-26 17:00:00', 'On Track'),
('HSTD2024090085', 16, 1, 200000000.00, 12, 'Vốn lưu động', 'Từ chối', 'Đã kết thúc', 3, 5, 5, 7, 'Kinh doanh thua lỗ liên tục 2 năm', '2024-09-14 10:15:00', '2024-09-26 15:00:00', '2024-09-28 17:00:00', 'On Track'),
('HSTD2024090086', 18, 1, 180000000.00, 12, 'Vốn kinh doanh', 'Từ chối', 'Đã kết thúc', 4, 6, 6, 7, 'Hồ sơ pháp lý không đầy đủ, không hợp tác cung cấp', '2024-09-16 11:00:00', '2024-09-28 14:00:00', '2024-09-30 17:00:00', 'On Track'),
('HSTD2024090087', 20, 2, 450000000.00, 24, 'Đầu tư', 'Từ chối', 'Đã kết thúc', 2, 5, 5, 7, 'Mục đích vay không phù hợp với chính sách ngân hàng', '2024-09-18 09:45:00', '2024-09-30 16:00:00', '2024-10-02 17:00:00', 'On Track'),
('HSTD2024090088', 24, 1, 220000000.00, 12, 'Vốn lưu động', 'Từ chối', 'Đã kết thúc', 3, 6, 6, 7, 'Đang có nợ xấu tại tổ chức tín dụng khác', '2024-09-20 10:30:00', '2024-10-02 15:00:00', '2024-10-04 17:00:00', 'On Track'),
('HSTD2024090089', 26, 1, 175000000.00, 12, 'Vốn kinh doanh', 'Từ chối', 'Đã kết thúc', 4, 5, 5, 7, 'Không chứng minh được nguồn thu hợp pháp', '2024-09-22 11:15:00', '2024-10-04 14:00:00', '2024-10-06 17:00:00', 'On Track'),
('HSTD2024090090', 28, 1, 140000000.00, 12, 'Vốn lưu động', 'Từ chối', 'Đã kết thúc', 2, 6, 6, 7, 'Khách hàng có hoạt động rửa tiền nghi ngờ', '2024-09-24 09:00:00', '2024-10-06 16:00:00', '2024-10-08 17:00:00', 'On Track'),
('HSTD2024090091', 30, 1, 195000000.00, 12, 'Vốn kinh doanh', 'Từ chối', 'Đã kết thúc', 3, 5, 5, 7, 'Ngành nghề kinh doanh thuộc danh sách hạn chế', '2024-09-26 10:00:00', '2024-10-08 15:00:00', '2024-10-10 17:00:00', 'On Track'),
('HSTD2024080092', 4, 1, 130000000.00, 12, 'Vốn lưu động', 'Từ chối', 'Đã kết thúc', 4, 6, 6, 7, 'Không đủ tuổi, chưa đủ điều kiện vay vốn', '2024-08-15 11:00:00', '2024-08-28 14:00:00', '2024-08-29 17:00:00', 'On Track'),
('HSTD2024080093', 10, 2, 500000000.00, 36, 'Đầu tư', 'Từ chối', 'Đã kết thúc', 2, 5, 5, 7, 'Dự án đầu tư không khả thi, rủi ro cao', '2024-08-20 09:30:00', '2024-09-02 16:00:00', '2024-09-03 17:00:00', 'On Track'),
('HSTD2024080094', 25, 1, 210000000.00, 12, 'Vốn kinh doanh', 'Từ chối', 'Đã kết thúc', 3, 6, 6, 7, 'Giá trị tài sản đảm bảo không đủ theo yêu cầu', '2024-08-25 10:15:00', '2024-09-07 15:00:00', '2024-09-08 17:00:00', 'On Track'),
('HSTD2024080095', 27, 1, 165000000.00, 12, 'Vốn lưu động', 'Từ chối', 'Đã kết thúc', 4, 5, 5, 7, 'Khách hàng không hợp tác trong quá trình thẩm định', '2024-08-30 11:00:00', '2024-09-12 14:00:00', '2024-09-13 17:00:00', 'On Track');

-- Yêu cầu bổ sung (Need More Info) - 10 records
INSERT INTO `credit_applications`
(`hstd_code`, `customer_id`, `product_id`, `amount`, `term_months`, `purpose`, `status`, `stage`, `created_by_id`, `assigned_to_id`, `reviewed_by_id`, `rejection_reason`, `created_at`, `updated_at`, `sla_target_date`, `sla_status`) VALUES
('HSTD2024090096', 2, 2, 550000000.00, 36, 'Đầu tư thiết bị', 'Yêu cầu bổ sung', 'Chờ bổ sung', 2, 5, 5, 'Cần bổ sung báo cáo tài chính 2 năm gần nhất có kiểm toán', '2024-09-17 09:00:00', '2024-09-30 16:00:00', '2024-10-14 17:00:00', 'Warning'),
('HSTD2024090097', 7, 1, 225000000.00, 12, 'Vốn lưu động', 'Yêu cầu bổ sung', 'Chờ bổ sung', 3, 6, 6, 'Cần bổ sung giấy tờ chứng minh quyền sở hữu tài sản đảm bảo', '2024-09-19 10:00:00', '2024-10-02 15:00:00', '2024-10-16 17:00:00', 'Warning'),
('HSTD2024090098', 11, 1, 185000000.00, 12, 'Vốn kinh doanh', 'Yêu cầu bổ sung', 'Chờ bổ sung', 4, 5, 5, 'Cần làm rõ nguồn gốc tiền tự có, bổ sung sao kê tài khoản 6 tháng', '2024-09-21 11:00:00', '2024-10-04 14:00:00', '2024-10-18 17:00:00', 'On Track'),
('HSTD2024090099', 13, 2, 620000000.00, 24, 'Mua xe', 'Yêu cầu bổ sung', 'Chờ bổ sung', 2, 6, 6, 'Cần bổ sung hợp đồng mua bán xe và hóa đơn VAT', '2024-09-23 09:30:00', '2024-10-06 16:00:00', '2024-10-20 17:00:00', 'On Track'),
('HSTD2024090100', 15, 1, 255000000.00, 12, 'Vốn lưu động', 'Yêu cầu bổ sung', 'Chờ bổ sung', 3, 5, 5, 'Cần bổ sung bảo hiểm tài sản đảm bảo, chưa mua theo yêu cầu', '2024-09-25 10:15:00', '2024-10-08 15:00:00', '2024-10-22 17:00:00', 'On Track'),
('HSTD2024090101', 17, 1, 295000000.00, 12, 'Vốn kinh doanh', 'Yêu cầu bổ sung', 'Chờ bổ sung', 4, 6, 6, 'Cần bổ sung giấy phép kinh doanh còn hiệu lực và giấy chứng nhận đăng ký kinh doanh', '2024-09-27 11:00:00', '2024-10-10 14:00:00', '2024-10-24 17:00:00', 'On Track'),
('HSTD2024090102', 19, 1, 205000000.00, 12, 'Vốn lưu động', 'Yêu cầu bổ sung', 'Chờ bổ sung', 2, 5, 5, 'Cần bổ sung hợp đồng kinh tế chứng minh mục đích vay vốn', '2024-09-29 09:45:00', '2024-10-12 16:00:00', '2024-10-26 17:00:00', 'On Track'),
('HSTD2024090103', 21, 1, 340000000.00, 12, 'Vốn kinh doanh', 'Yêu cầu bổ sung', 'Chờ bổ sung', 3, 6, 6, 'Cần giải trình rõ các khoản chi lớn bất thường trong 3 tháng gần đây', '2024-10-01 10:30:00', '2024-10-14 15:00:00', '2024-10-28 17:00:00', 'On Track'),
('HSTD2024090104', 23, 2, 680000000.00, 36, 'Đầu tư thiết bị', 'Yêu cầu bổ sung', 'Chờ bổ sung', 4, 5, 5, 'Cần bổ sung báo cáo thẩm định giá tài sản từ tổ chức có chứng chỉ', '2024-10-03 11:15:00', '2024-10-16 14:00:00', '2024-10-30 17:00:00', 'On Track'),
('HSTD2024090105', 29, 1, 245000000.00, 12, 'Vốn lưu động', 'Yêu cầu bổ sung', 'Chờ bổ sung', 2, 6, 6, 'Cần bổ sung giấy tờ nhân thân của người bảo lãnh', '2024-10-05 09:00:00', '2024-10-18 16:00:00', '2024-11-01 17:00:00', 'On Track');

-- Đã hủy (Cancelled Applications) - 5 records
INSERT INTO `credit_applications`
(`hstd_code`, `customer_id`, `product_id`, `amount`, `term_months`, `purpose`, `status`, `stage`, `created_by_id`, `assigned_to_id`, `rejection_reason`, `created_at`, `updated_at`, `sla_target_date`, `sla_status`) VALUES
('HSTD2024090106', 3, 1, 200000000.00, 12, 'Vốn lưu động', 'Đã hủy', 'Đã kết thúc', 2, 2, 'Khách hàng yêu cầu hủy do đã vay được tại ngân hàng khác với lãi suất tốt hơn', '2024-09-11 09:00:00', '2024-09-13 10:00:00', '2024-09-25 17:00:00', 'On Track'),
('HSTD2024090107', 5, 2, 480000000.00, 24, 'Mua xe', 'Đã hủy', 'Đã kết thúc', 3, 3, 'Khách hàng không còn nhu cầu vay, đã thanh toán bằng tiền mặt', '2024-09-13 10:00:00', '2024-09-15 11:00:00', '2024-09-27 17:00:00', 'On Track'),
('HSTD2024090108', 9, 1, 155000000.00, 12, 'Vốn kinh doanh', 'Đã hủy', 'Đã kết thúc', 4, 4, 'Khách hàng hủy do không đồng ý với lãi suất và điều kiện vay', '2024-09-15 11:00:00', '2024-09-17 09:00:00', '2024-09-29 17:00:00', 'On Track'),
('HSTD2024080109', 11, 1, 175000000.00, 12, 'Vốn lưu động', 'Đã hủy', 'Đã kết thúc', 2, 2, 'Khách hàng thay đổi kế hoạch kinh doanh, không cần vốn nữa', '2024-08-10 09:30:00', '2024-08-12 10:30:00', '2024-08-24 17:00:00', 'On Track'),
('HSTD2024080110', 16, 1, 230000000.00, 12, 'Vốn kinh doanh', 'Đã hủy', 'Đã kết thúc', 3, 3, 'Khách hàng không hợp tác cung cấp hồ sơ, tự ý hủy hồ sơ', '2024-08-15 10:15:00', '2024-08-17 11:15:00', '2024-08-29 17:00:00', 'On Track');

-- ============================================================================
-- 5. FACILITIES (40 facilities for approved applications)
-- ============================================================================
-- Note: application_id will be sequential IDs (41-80) based on insertion order

INSERT INTO `facilities`
(`application_id`, `facility_code`, `facility_type`, `product_id`, `amount`, `disbursed_amount`, `currency`, `status`, `start_date`, `end_date`, `interest_rate`, `collateral_required`, `collateral_activated`, `created_by_id`, `approved_by_id`, `created_at`) VALUES
(41, 'FAC-2024080001', 'Vốn lưu động', 1, 350000000.00, 350000000.00, 'VND', 'Active', '2024-08-20', '2025-08-20', 8.50, 1, 1, 2, 7, '2024-08-16 09:00:00'),
(42, 'FAC-2024080002', 'Đầu tư dài hạn', 2, 6000000000.00, 4500000000.00, 'VND', 'Active', '2024-08-25', '2028-08-25', 9.20, 1, 1, 3, 9, '2024-08-21 10:00:00'),
(43, 'FAC-2024080003', 'Vốn lưu động', 1, 280000000.00, 280000000.00, 'VND', 'Active', '2024-08-25', '2025-08-25', 8.30, 1, 1, 2, 7, '2024-08-23 11:00:00'),
(44, 'FAC-2024080004', 'Tài trợ thương mại', 3, 3500000000.00, 3500000000.00, 'VND', 'Active', '2024-08-28', '2025-02-28', 7.80, 1, 1, 4, 8, '2024-08-25 09:30:00'),
(45, 'FAC-2024080005', 'Vốn lưu động', 1, 320000000.00, 320000000.00, 'VND', 'Active', '2024-08-30', '2025-08-30', 8.40, 1, 1, 3, 7, '2024-08-27 10:15:00'),
(46, 'FAC-2024080006', 'Thấu chi', 4, 10000000000.00, 5200000000.00, 'VND', 'Active', '2024-09-01', '2025-09-01', 10.50, 1, 1, 2, 9, '2024-08-30 11:00:00'),
(47, 'FAC-2024080007', 'Đầu tư trung hạn', 2, 700000000.00, 700000000.00, 'VND', 'Active', '2024-09-05', '2027-09-05', 9.00, 1, 1, 4, 7, '2024-09-01 09:45:00'),
(48, 'FAC-2024080008', 'Vốn lưu động', 1, 5500000000.00, 5500000000.00, 'VND', 'Active', '2024-09-08', '2025-09-08', 8.90, 1, 1, 3, 9, '2024-09-03 10:30:00'),
(49, 'FAC-2024080009', 'Vốn lưu động', 1, 390000000.00, 390000000.00, 'VND', 'Active', '2024-09-10', '2025-09-10', 8.60, 1, 1, 2, 7, '2024-09-05 11:15:00'),
(50, 'FAC-2024080010', 'Đầu tư dài hạn', 2, 8500000000.00, 6000000000.00, 'VND', 'Active', '2024-09-12', '2029-09-12', 9.50, 1, 1, 4, 9, '2024-09-07 09:00:00'),
(51, 'FAC-2024080011', 'Vốn lưu động', 1, 260000000.00, 260000000.00, 'VND', 'Active', '2024-09-15', '2025-09-15', 8.20, 1, 1, 3, 7, '2024-09-09 10:00:00'),
(52, 'FAC-2024080012', 'Tài trợ thương mại', 3, 2800000000.00, 2800000000.00, 'VND', 'Active', '2024-09-18', '2025-03-18', 7.70, 1, 1, 2, 8, '2024-09-11 11:30:00'),
(53, 'FAC-2024080013', 'Vốn lưu động', 1, 410000000.00, 410000000.00, 'VND', 'Active', '2024-09-20', '2025-09-20', 8.70, 1, 1, 4, 7, '2024-09-13 09:15:00'),
(54, 'FAC-2024080014', 'Đầu tư dài hạn', 2, 9000000000.00, 7000000000.00, 'VND', 'Active', '2024-09-22', '2028-09-22', 9.30, 1, 1, 3, 9, '2024-09-15 10:00:00'),
(55, 'FAC-2024080015', 'Vốn lưu động', 1, 4200000000.00, 4200000000.00, 'VND', 'Active', '2024-09-25', '2025-09-25', 8.80, 1, 1, 2, 7, '2024-09-17 11:00:00'),
(56, 'FAC-2024080016', 'Tài trợ thương mại', 3, 3200000000.00, 3200000000.00, 'VND', 'Active', '2024-09-28', '2025-03-28', 7.90, 1, 1, 4, 8, '2024-09-19 09:30:00'),
(57, 'FAC-2024080017', 'Thấu chi', 4, 7500000000.00, 3800000000.00, 'VND', 'Active', '2024-10-01', '2025-10-01', 10.80, 1, 1, 3, 9, '2024-09-21 10:15:00'),
(58, 'FAC-2024080018', 'Đầu tư dài hạn', 2, 11000000000.00, 8000000000.00, 'VND', 'Active', '2024-10-05', '2029-10-05', 9.60, 1, 1, 2, 9, '2024-09-23 11:00:00'),
(59, 'FAC-2024080019', 'Vốn lưu động', 1, 6200000000.00, 6200000000.00, 'VND', 'Active', '2024-10-08', '2025-10-08', 8.95, 1, 1, 4, 9, '2024-09-25 09:45:00'),
(60, 'FAC-2024080020', 'Đầu tư dài hạn', 2, 9500000000.00, 7500000000.00, 'VND', 'Active', '2024-10-10', '2028-10-10', 9.40, 1, 1, 3, 9, '2024-09-26 10:30:00'),
(61, 'FAC-2024070001', 'Vốn lưu động', 1, 160000000.00, 160000000.00, 'VND', 'Active', '2024-07-20', '2025-07-20', 8.10, 1, 1, 2, 7, '2024-07-16 09:00:00'),
(62, 'FAC-2024070002', 'Vốn lưu động', 1, 4800000000.00, 4800000000.00, 'VND', 'Active', '2024-07-25', '2025-07-25', 8.75, 1, 1, 3, 7, '2024-07-18 10:00:00'),
(63, 'FAC-2024070003', 'Đầu tư trung hạn', 2, 520000000.00, 520000000.00, 'VND', 'Active', '2024-07-28', '2027-07-28', 8.85, 1, 1, 4, 7, '2024-07-20 11:00:00'),
(64, 'FAC-2024070004', 'Tài trợ thương mại', 3, 2600000000.00, 2600000000.00, 'VND', 'Active', '2024-07-30', '2025-01-30', 7.60, 1, 1, 2, 8, '2024-07-22 09:30:00'),
(65, 'FAC-2024070005', 'Vốn lưu động', 1, 240000000.00, 240000000.00, 'VND', 'Active', '2024-08-02', '2025-08-02', 8.25, 1, 1, 3, 7, '2024-07-24 10:15:00'),
(66, 'FAC-2024070006', 'Đầu tư dài hạn', 2, 7800000000.00, 6500000000.00, 'VND', 'Active', '2024-08-05', '2028-08-05', 9.25, 1, 1, 4, 9, '2024-07-26 11:00:00'),
(67, 'FAC-2024070007', 'Vốn lưu động', 1, 195000000.00, 195000000.00, 'VND', 'Active', '2024-08-08', '2025-08-08', 8.15, 1, 1, 2, 7, '2024-07-28 09:45:00'),
(68, 'FAC-2024070008', 'Thấu chi', 4, 6500000000.00, 2900000000.00, 'VND', 'Active', '2024-08-10', '2025-08-10', 10.60, 1, 1, 3, 9, '2024-07-30 10:30:00'),
(69, 'FAC-2024070009', 'Vốn lưu động', 1, 215000000.00, 215000000.00, 'VND', 'Active', '2024-08-12', '2025-08-12', 8.35, 1, 1, 4, 7, '2024-08-01 11:15:00'),
(70, 'FAC-2024070010', 'Đầu tư dài hạn', 2, 8200000000.00, 6800000000.00, 'VND', 'Active', '2024-08-15', '2029-08-15', 9.45, 1, 1, 2, 9, '2024-08-03 09:00:00'),
(71, 'FAC-2024060001', 'Vốn lưu động', 1, 275000000.00, 275000000.00, 'VND', 'Active', '2024-06-20', '2025-06-20', 8.00, 1, 1, 3, 7, '2024-06-16 10:00:00'),
(72, 'FAC-2024060002', 'Vốn lưu động', 1, 3900000000.00, 3900000000.00, 'VND', 'Active', '2024-06-25', '2025-06-25', 8.65, 1, 1, 4, 7, '2024-06-20 11:00:00'),
(73, 'FAC-2024060003', 'Đầu tư trung hạn', 2, 580000000.00, 580000000.00, 'VND', 'Active', '2024-06-28', '2026-06-28', 8.75, 1, 1, 2, 7, '2024-06-25 09:30:00'),
(74, 'FAC-2024060004', 'Tài trợ thương mại', 3, 2900000000.00, 2900000000.00, 'VND', 'Active', '2024-07-01', '2024-12-31', 7.50, 1, 1, 3, 8, '2024-06-30 10:15:00'),
(75, 'FAC-2024060005', 'Vốn lưu động', 1, 335000000.00, 335000000.00, 'VND', 'Active', '2024-07-08', '2025-07-08', 8.45, 1, 1, 4, 7, '2024-07-05 11:00:00'),
(76, 'FAC-2024060006', 'Đầu tư dài hạn', 2, 10500000000.00, 8500000000.00, 'VND', 'Active', '2024-07-12', '2029-07-12', 9.70, 1, 1, 2, 9, '2024-07-10 09:45:00'),
(77, 'FAC-2024050001', 'Vốn lưu động', 1, 290000000.00, 290000000.00, 'VND', 'Active', '2024-05-25', '2025-05-25', 7.95, 1, 1, 3, 7, '2024-05-20 10:00:00'),
(78, 'FAC-2024050002', 'Tài trợ thương mại', 3, 3100000000.00, 3100000000.00, 'VND', 'Active', '2024-05-28', '2024-11-28', 7.40, 1, 1, 4, 8, '2024-05-25 11:00:00'),
(79, 'FAC-2024050003', 'Vốn lưu động', 1, 355000000.00, 355000000.00, 'VND', 'Active', '2024-06-01', '2025-06-01', 8.55, 1, 1, 2, 7, '2024-05-30 09:30:00'),
(80, 'FAC-2024050004', 'Thấu chi', 4, 9200000000.00, 4100000000.00, 'VND', 'Active', '2024-06-05', '2025-06-05', 10.90, 1, 1, 3, 9, '2024-06-04 10:15:00');

-- ============================================================================
-- 6. DISBURSEMENTS (60+ disbursements with diverse statuses)
-- ============================================================================
-- Note: facility_id will be 1-40, application_id will be 41-80

INSERT INTO `disbursements`
(`disbursement_code`, `application_id`, `facility_id`, `disbursement_type`, `amount`, `currency`, `purpose`, `beneficiary_type`, `beneficiary_name`, `beneficiary_account`, `beneficiary_bank`, `status`, `stage`, `assigned_to_id`, `created_by_id`, `checked_by_id`, `approved_by_id`, `executed_by_id`, `requested_date`, `approved_date`, `disbursed_date`, `created_at`, `updated_at`) VALUES
-- Executed disbursements (completed)
('GN-2024080001', 41, 1, 'Lần đầu', 350000000.00, 'VND', 'Giải ngân vốn lưu động kinh doanh', 'Chính chủ', 'Nguyễn Văn Anh', '1234567890', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 7, 11, '2024-08-20', '2024-08-22', '2024-08-23', '2024-08-20 09:00:00', '2024-08-23 14:00:00'),
('GN-2024080002', 42, 2, 'Lần đầu', 3000000000.00, 'VND', 'Thanh toán hợp đồng mua máy móc', 'Bên thứ 3', 'Công ty ABC Machinery', '9876543210', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-08-26', '2024-08-28', '2024-08-29', '2024-08-26 10:00:00', '2024-08-29 15:00:00'),
('GN-2024080003', 42, 2, 'Giải ngân theo tiến độ', 1500000000.00, 'VND', 'Giải ngân đợt 2 theo tiến độ dự án', 'Bên thứ 3', 'Công ty ABC Machinery', '9876543210', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-09-15', '2024-09-17', '2024-09-18', '2024-09-15 10:00:00', '2024-09-18 16:00:00'),
('GN-2024080004', 43, 3, 'Lần đầu', 280000000.00, 'VND', 'Vốn lưu động kinh doanh', 'Chính chủ', 'Lê Văn Cường', '2345678901', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 7, 11, '2024-08-26', '2024-08-28', '2024-08-29', '2024-08-26 11:00:00', '2024-08-29 14:00:00'),
('GN-2024080005', 44, 4, 'Lần đầu', 3500000000.00, 'VND', 'Thanh toán L/C xuất khẩu', 'Bên thứ 3', 'Global Trading Co', '3456789012', 'HSBC', 'Executed', 'Đã giải ngân', 10, 4, 10, 8, 11, '2024-08-29', '2024-08-30', '2024-09-02', '2024-08-29 09:30:00', '2024-09-02 15:00:00'),
('GN-2024080006', 45, 5, 'Lần đầu', 320000000.00, 'VND', 'Vốn mua hàng hóa', 'Chính chủ', 'Phạm Thị Dung', '4567890123', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 7, 11, '2024-09-01', '2024-09-03', '2024-09-04', '2024-09-01 10:15:00', '2024-09-04 14:00:00'),
('GN-2024080007', 46, 6, 'Lần đầu', 3000000000.00, 'VND', 'Rút vốn thấu chi đợt 1', 'Chính chủ', 'Công ty TNHH Thương Mại ABC', '5678901234', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 9, 11, '2024-09-02', '2024-09-04', '2024-09-05', '2024-09-02 11:00:00', '2024-09-05 15:00:00'),
('GN-2024080008', 46, 6, 'Rút vốn', 2200000000.00, 'VND', 'Rút vốn thấu chi đợt 2', 'Chính chủ', 'Công ty TNHH Thương Mại ABC', '5678901234', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 9, 11, '2024-10-01', '2024-10-03', '2024-10-04', '2024-10-01 09:00:00', '2024-10-04 14:00:00'),
('GN-2024080009', 47, 7, 'Lần đầu', 700000000.00, 'VND', 'Mua xe ô tô phục vụ kinh doanh', 'Bên thứ 3', 'Toyota Việt Nam', '6789012345', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 4, 10, 7, 11, '2024-09-06', '2024-09-08', '2024-09-09', '2024-09-06 09:45:00', '2024-09-09 16:00:00'),
('GN-2024080010', 48, 8, 'Lần đầu', 5500000000.00, 'VND', 'Vốn lưu động doanh nghiệp', 'Chính chủ', 'Công ty TNHH Vận Tải Thành Đạt', '7890123456', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-09-09', '2024-09-11', '2024-09-12', '2024-09-09 10:30:00', '2024-09-12 15:00:00'),
('GN-2024080011', 49, 9, 'Lần đầu', 390000000.00, 'VND', 'Vốn kinh doanh', 'Chính chủ', 'Bùi Thị Hằng', '8901234567', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 7, 11, '2024-09-11', '2024-09-13', '2024-09-14', '2024-09-11 11:15:00', '2024-09-14 14:00:00'),
('GN-2024080012', 50, 10, 'Lần đầu', 4000000000.00, 'VND', 'Thanh toán xây dựng nhà máy giai đoạn 1', 'Bên thứ 3', 'Công ty Xây Dựng 579', '9012345678', 'ACB', 'Executed', 'Đã giải ngân', 10, 4, 10, 9, 11, '2024-09-13', '2024-09-15', '2024-09-16', '2024-09-13 09:00:00', '2024-09-16 16:00:00'),
('GN-2024080013', 50, 10, 'Giải ngân theo tiến độ', 2000000000.00, 'VND', 'Xây dựng nhà máy giai đoạn 2', 'Bên thứ 3', 'Công ty Xây Dựng 579', '9012345678', 'ACB', 'Executed', 'Đã giải ngân', 10, 4, 10, 9, 11, '2024-10-05', '2024-10-07', '2024-10-08', '2024-10-05 10:00:00', '2024-10-08 15:00:00'),
('GN-2024080014', 51, 11, 'Lần đầu', 260000000.00, 'VND', 'Vốn lưu động', 'Chính chủ', 'Đinh Thị Hoa', '0123456789', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 7, 11, '2024-09-16', '2024-09-18', '2024-09-19', '2024-09-16 10:00:00', '2024-09-19 14:00:00'),
('GN-2024080015', 52, 12, 'Lần đầu', 2800000000.00, 'VND', 'Thanh toán L/C thương mại', 'Bên thứ 3', 'Import Export JSC', '1234567899', 'Techcombank', 'Executed', 'Đã giải ngân', 10, 2, 10, 8, 11, '2024-09-19', '2024-09-21', '2024-09-23', '2024-09-19 11:30:00', '2024-09-23 15:00:00'),
('GN-2024080016', 53, 13, 'Lần đầu', 410000000.00, 'VND', 'Vốn kinh doanh', 'Chính chủ', 'Trần Thị Bình', '2345678990', 'UBank', 'Executed', 'Đã giải ngân', 10, 4, 10, 7, 11, '2024-09-21', '2024-09-23', '2024-09-24', '2024-09-21 09:15:00', '2024-09-24 14:00:00'),
('GN-2024080017', 54, 14, 'Lần đầu', 5000000000.00, 'VND', 'Đầu tư cơ sở hạ tầng giai đoạn 1', 'Bên thứ 3', 'Infrastructure Co', '3456789990', 'Vietinbank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-09-23', '2024-09-25', '2024-09-26', '2024-09-23 10:00:00', '2024-09-26 16:00:00'),
('GN-2024080018', 54, 14, 'Giải ngân theo tiến độ', 2000000000.00, 'VND', 'Đầu tư CSHT giai đoạn 2', 'Bên thứ 3', 'Infrastructure Co', '3456789990', 'Vietinbank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-10-15', '2024-10-17', '2024-10-18', '2024-10-15 11:00:00', '2024-10-18 15:00:00'),
('GN-2024080019', 55, 15, 'Lần đầu', 4200000000.00, 'VND', 'Vốn lưu động DN', 'Chính chủ', 'Công ty CP Xuất Nhập Khẩu Hòa Bình', '4567899990', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 7, 11, '2024-09-26', '2024-09-28', '2024-09-30', '2024-09-26 11:00:00', '2024-09-30 14:00:00'),
('GN-2024080020', 56, 16, 'Lần đầu', 3200000000.00, 'VND', 'Tài trợ xuất khẩu', 'Bên thứ 3', 'Export Partner Ltd', '5678999990', 'BIDV', 'Executed', 'Đã giải ngân', 10, 4, 10, 8, 11, '2024-09-29', '2024-10-01', '2024-10-02', '2024-09-29 09:30:00', '2024-10-02 15:00:00'),
('GN-2024080021', 57, 17, 'Lần đầu', 2500000000.00, 'VND', 'Rút vốn thấu chi', 'Chính chủ', 'Công ty TNHH Công Nghệ Số 1', '6789099990', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-10-02', '2024-10-04', '2024-10-05', '2024-10-02 10:15:00', '2024-10-05 14:00:00'),
('GN-2024080022', 57, 17, 'Rút vốn', 1300000000.00, 'VND', 'Rút vốn thấu chi đợt 2', 'Chính chủ', 'Công ty TNHH Công Nghệ Số 1', '6789099990', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-10-20', '2024-10-22', '2024-10-23', '2024-10-20 11:00:00', '2024-10-23 15:00:00'),
('GN-2024080023', 58, 18, 'Lần đầu', 6000000000.00, 'VND', 'Đầu tư dự án lớn giai đoạn 1', 'Bên thứ 3', 'Big Project JSC', '7890199990', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 2, 10, 9, 11, '2024-10-06', '2024-10-08', '2024-10-09', '2024-10-06 11:00:00', '2024-10-09 16:00:00'),
('GN-2024080024', 58, 18, 'Giải ngân theo tiến độ', 2000000000.00, 'VND', 'Dự án lớn giai đoạn 2', 'Bên thứ 3', 'Big Project JSC', '7890199990', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 2, 10, 9, 11, '2024-10-25', '2024-10-27', '2024-10-28', '2024-10-25 09:00:00', '2024-10-28 15:00:00'),
('GN-2024080025', 59, 19, 'Lần đầu', 6200000000.00, 'VND', 'Vốn lưu động', 'Chính chủ', 'Công ty TNHH Cơ Khí Chính Xác', '8901299990', 'UBank', 'Executed', 'Đã giải ngân', 10, 4, 10, 9, 11, '2024-10-09', '2024-10-11', '2024-10-14', '2024-10-09 09:45:00', '2024-10-14 14:00:00'),
('GN-2024080026', 60, 20, 'Lần đầu', 5500000000.00, 'VND', 'Mua sắm máy móc giai đoạn 1', 'Bên thứ 3', 'Machine Supplier Co', '9012399990', 'ACB', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-10-11', '2024-10-13', '2024-10-15', '2024-10-11 10:30:00', '2024-10-15 16:00:00'),
('GN-2024080027', 60, 20, 'Giải ngân theo tiến độ', 2000000000.00, 'VND', 'Mua máy móc giai đoạn 2', 'Bên thứ 3', 'Machine Supplier Co', '9012399990', 'ACB', 'Approved', 'Chờ giải ngân', 10, 3, 10, 9, NULL, '2024-10-28', '2024-10-30', NULL, '2024-10-28 11:00:00', '2024-10-30 14:00:00'),
-- Recent executed disbursements (July-October)
('GN-2024070001', 61, 21, 'Lần đầu', 160000000.00, 'VND', 'Vốn lưu động', 'Chính chủ', 'Nguyễn Văn Anh', '1234567890', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 7, 11, '2024-07-21', '2024-07-23', '2024-07-24', '2024-07-21 09:00:00', '2024-07-24 14:00:00'),
('GN-2024070002', 62, 22, 'Lần đầu', 4800000000.00, 'VND', 'Vốn lưu động DN', 'Chính chủ', 'Công ty Cổ phần Đầu Tư XYZ', '0123456780', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 7, 11, '2024-07-26', '2024-07-28', '2024-07-29', '2024-07-26 10:00:00', '2024-07-29 15:00:00'),
('GN-2024070003', 63, 23, 'Lần đầu', 520000000.00, 'VND', 'Đầu tư thiết bị', 'Bên thứ 3', 'Equipment Co', '1234567780', 'Techcombank', 'Executed', 'Đã giải ngân', 10, 4, 10, 7, 11, '2024-07-29', '2024-07-31', '2024-08-01', '2024-07-29 11:00:00', '2024-08-01 14:00:00'),
('GN-2024070004', 64, 24, 'Lần đầu', 2600000000.00, 'VND', 'Tài trợ nhập khẩu', 'Bên thứ 3', 'Import Co', '2345678780', 'BIDV', 'Executed', 'Đã giải ngân', 10, 2, 10, 8, 11, '2024-08-01', '2024-08-03', '2024-08-05', '2024-08-01 09:30:00', '2024-08-05 15:00:00'),
('GN-2024070005', 65, 25, 'Lần đầu', 240000000.00, 'VND', 'Vốn kinh doanh', 'Chính chủ', 'Lê Văn Cường', '3456788780', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 7, 11, '2024-08-03', '2024-08-05', '2024-08-06', '2024-08-03 10:15:00', '2024-08-06 14:00:00'),
('GN-2024070006', 66, 26, 'Lần đầu', 4500000000.00, 'VND', 'Đầu tư mở rộng giai đoạn 1', 'Bên thứ 3', 'Expansion JSC', '4567897780', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 4, 10, 9, 11, '2024-08-06', '2024-08-08', '2024-08-09', '2024-08-06 11:00:00', '2024-08-09 16:00:00'),
('GN-2024070007', 66, 26, 'Giải ngân theo tiến độ', 2000000000.00, 'VND', 'Mở rộng giai đoạn 2', 'Bên thứ 3', 'Expansion JSC', '4567897780', 'Vietcombank', 'Executed', 'Đã giải ngân', 10, 4, 10, 9, 11, '2024-09-20', '2024-09-22', '2024-09-23', '2024-09-20 09:00:00', '2024-09-23 15:00:00'),
('GN-2024070008', 67, 27, 'Lần đầu', 195000000.00, 'VND', 'Vốn lưu động', 'Chính chủ', 'Ngô Văn Huy', '5678906780', 'UBank', 'Executed', 'Đã giải ngân', 10, 2, 10, 7, 11, '2024-08-09', '2024-08-11', '2024-08-12', '2024-08-09 09:45:00', '2024-08-12 14:00:00'),
('GN-2024070009', 68, 28, 'Lần đầu', 2000000000.00, 'VND', 'Rút vốn thấu chi', 'Chính chủ', 'Công ty TNHH Sản Xuất Minh Phát', '6789015780', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-08-11', '2024-08-13', '2024-08-14', '2024-08-11 10:30:00', '2024-08-14 15:00:00'),
('GN-2024070010', 68, 28, 'Rút vốn', 900000000.00, 'VND', 'Rút vốn thấu chi đợt 2', 'Chính chủ', 'Công ty TNHH Sản Xuất Minh Phát', '6789015780', 'UBank', 'Executed', 'Đã giải ngân', 10, 3, 10, 9, 11, '2024-09-25', '2024-09-27', '2024-09-28', '2024-09-25 11:00:00', '2024-09-28 14:00:00'),
('GN-2024070011', 69, 29, 'Lần đầu', 215000000.00, 'VND', 'Vốn kinh doanh', 'Chính chủ', 'Đinh Thị Hoa', '7890124780', 'UBank', 'Executed', 'Đã giải ngân', 10, 4, 10, 7, 11, '2024-08-13', '2024-08-15', '2024-08-16', '2024-08-13 11:15:00', '2024-08-16 14:00:00'),
('GN-2024070012', 70, 30, 'Lần đầu', 5000000000.00, 'VND', 'Xây dựng nhà máy giai đoạn 1', 'Bên thứ 3', 'Construction 789', '8901233780', 'ACB', 'Executed', 'Đã giải ngân', 10, 2, 10, 9, 11, '2024-08-16', '2024-08-18', '2024-08-19', '2024-08-16 09:00:00', '2024-08-19 16:00:00'),
('GN-2024070013', 70, 30, 'Giải ngân theo tiến độ', 1800000000.00, 'VND', 'Nhà máy giai đoạn 2', 'Bên thứ 3', 'Construction 789', '8901233780', 'ACB', 'Executed', 'Đã giải ngân', 10, 2, 10, 9, 11, '2024-10-01', '2024-10-03', '2024-10-04', '2024-10-01 10:00:00', '2024-10-04 15:00:00'),
-- Approved (waiting for execution)
('GN-2024060001', 71, 31, 'Lần đầu', 275000000.00, 'VND', 'Vốn lưu động', 'Chính chủ', 'Dương Văn Minh', '9012342780', 'UBank', 'Approved', 'Chờ giải ngân', 10, 3, 10, 7, NULL, '2024-06-21', '2024-06-25', NULL, '2024-06-21 10:00:00', '2024-06-25 14:00:00'),
('GN-2024060002', 72, 32, 'Lần đầu', 3900000000.00, 'VND', 'Vốn lưu động DN', 'Chính chủ', 'Công ty Cổ phần May Mặc Việt Nam', '0123451780', 'UBank', 'Approved', 'Chờ giải ngân', 10, 4, 10, 7, NULL, '2024-06-26', '2024-06-28', NULL, '2024-06-26 11:00:00', '2024-06-28 15:00:00'),
('GN-2024100001', 73, 33, 'Lần đầu', 580000000.00, 'VND', 'Mua xe ô tô', 'Bên thứ 3', 'Auto Dealer Ltd', '1234560780', 'Vietcombank', 'Approved', 'Chờ giải ngân', 10, 2, 10, 7, NULL, '2024-10-29', '2024-10-30', NULL, '2024-10-29 09:30:00', '2024-10-30 16:00:00'),
('GN-2024100002', 74, 34, 'Lần đầu', 2900000000.00, 'VND', 'Tài trợ thương mại', 'Bên thứ 3', 'Trade Partner Co', '2345669780', 'BIDV', 'Approved', 'Chờ giải ngân', 10, 3, 10, 8, NULL, '2024-10-29', '2024-10-30', NULL, '2024-10-29 10:15:00', '2024-10-30 15:00:00'),
-- Awaiting Conditions Check
('GN-2024100003', 75, 35, 'Lần đầu', 335000000.00, 'VND', 'Vốn kinh doanh', 'Chính chủ', 'Lý Văn Oanh', '3456778780', 'UBank', 'Awaiting Conditions Check', 'Kiểm tra điều kiện', 10, 4, 10, NULL, NULL, '2024-10-28', NULL, NULL, '2024-10-28 11:00:00', '2024-10-29 10:00:00'),
('GN-2024100004', 76, 36, 'Lần đầu', 6500000000.00, 'VND', 'Đầu tư dự án giai đoạn 1', 'Bên thứ 3', 'Project Developer', '4567887780', 'Vietinbank', 'Awaiting Conditions Check', 'Kiểm tra điều kiện', 10, 2, 10, NULL, NULL, '2024-10-29', NULL, NULL, '2024-10-29 09:45:00', '2024-10-30 11:00:00'),
('GN-2024100005', 77, 37, 'Lần đầu', 290000000.00, 'VND', 'Vốn lưu động', 'Chính chủ', 'Tô Văn Thái', '5678996780', 'UBank', 'Awaiting Conditions Check', 'Kiểm tra điều kiện', 10, 3, 10, NULL, NULL, '2024-10-29', NULL, NULL, '2024-10-29 10:00:00', '2024-10-30 09:00:00'),
-- Awaiting Approval
('GN-2024100006', 78, 38, 'Lần đầu', 3100000000.00, 'VND', 'Tài trợ xuất khẩu', 'Bên thứ 3', 'Export Company', '6789005780', 'Techcombank', 'Awaiting Approval', 'Chờ phê duyệt', 10, 4, 10, NULL, NULL, '2024-10-30', NULL, NULL, '2024-10-30 11:00:00', '2024-10-30 14:00:00'),
('GN-2024100007', 79, 39, 'Lần đầu', 355000000.00, 'VND', 'Vốn kinh doanh', 'Chính chủ', 'Phan Thị Xuân', '7890114780', 'UBank', 'Awaiting Approval', 'Chờ phê duyệt', 10, 2, 10, NULL, NULL, '2024-10-30', NULL, NULL, '2024-10-30 09:30:00', '2024-10-30 13:00:00'),
-- Draft
('GN-2024100008', 80, 40, 'Lần đầu', 3000000000.00, 'VND', 'Rút vốn thấu chi đợt 1', 'Chính chủ', 'Công ty Cổ phần Năng Lượng Tái Tạo', '8901223780', 'UBank', 'Draft', 'Khởi tạo', 10, 3, NULL, NULL, NULL, '2024-10-30', NULL, NULL, '2024-10-30 10:15:00', '2024-10-30 10:15:00'),
('GN-2024100009', 80, 40, 'Rút vốn', 1100000000.00, 'VND', 'Rút vốn thấu chi đợt 2', 'Chính chủ', 'Công ty Cổ phần Năng Lượng Tái Tạo', '8901223780', 'UBank', 'Draft', 'Khởi tạo', 10, 3, NULL, NULL, NULL, '2024-10-30', NULL, NULL, '2024-10-30 11:30:00', '2024-10-30 11:30:00'),
-- Rejected
('GN-2024090001', 54, 14, 'Giải ngân theo tiến độ', 1000000000.00, 'VND', 'Giai đoạn 3 (bị từ chối)', 'Bên thứ 3', 'Infrastructure Co', '3456789990', 'Vietinbank', 'Rejected', 'Đã từ chối', 10, 3, 10, 9, NULL, '2024-10-20', NULL, NULL, '2024-10-20 09:00:00', '2024-10-22 15:00:00'),
-- Cancelled
('GN-2024090002', 58, 18, 'Giải ngân theo tiến độ', 500000000.00, 'VND', 'Giai đoạn bổ sung (đã hủy)', 'Bên thứ 3', 'Big Project JSC', '7890199990', 'Vietcombank', 'Cancelled', 'Đã hủy', 10, 2, NULL, NULL, NULL, '2024-10-18', NULL, NULL, '2024-10-18 10:00:00', '2024-10-20 11:00:00');

-- ============================================================================
-- 7. APPLICATION DOCUMENTS (Sample documents for applications)
-- ============================================================================

INSERT INTO `application_documents`
(`application_id`, `document_definition_id`, `document_name`, `file_path`, `file_type`, `file_size`, `uploaded_by_id`, `uploaded_at`, `version`, `is_latest`) VALUES
-- Documents for approved applications
(41, 1, 'CCCD khách hàng', 'uploads/docs/2024/08/HSTD2024080041_cccd.pdf', 'application/pdf', 2456789, 2, '2024-08-16 10:00:00', 1, 1),
(41, 2, 'Sao kê tài khoản 6 tháng', 'uploads/docs/2024/08/HSTD2024080041_bank_statement.pdf', 'application/pdf', 5234567, 2, '2024-08-16 10:30:00', 1, 1),
(42, 1, 'Giấy ĐKKD công ty', 'uploads/docs/2024/08/HSTD2024080042_business_license.pdf', 'application/pdf', 3456789, 3, '2024-08-21 11:00:00', 1, 1),
(42, 2, 'Báo cáo tài chính 2023', 'uploads/docs/2024/08/HSTD2024080042_financial_2023.pdf', 'application/pdf', 8234567, 3, '2024-08-21 11:30:00', 1, 1),
(42, 3, 'Hợp đồng mua máy móc', 'uploads/docs/2024/08/HSTD2024080042_contract.pdf', 'application/pdf', 4567890, 3, '2024-08-21 12:00:00', 1, 1),
(43, 1, 'CCCD Lê Văn Cường', 'uploads/docs/2024/08/HSTD2024080043_cccd.pdf', 'application/pdf', 2345678, 2, '2024-08-23 12:00:00', 1, 1),
(43, 4, 'Giấy tờ nhà đất (TSĐB)', 'uploads/docs/2024/08/HSTD2024080043_property_cert.pdf', 'application/pdf', 3456789, 2, '2024-08-23 12:30:00', 1, 1),
(44, 1, 'Giấy ĐKKD', 'uploads/docs/2024/08/HSTD2024080044_license.pdf', 'application/pdf', 2987654, 4, '2024-08-25 10:00:00', 1, 1),
(44, 3, 'Hợp đồng xuất khẩu', 'uploads/docs/2024/08/HSTD2024080044_export_contract.pdf', 'application/pdf', 6543210, 4, '2024-08-25 10:30:00', 1, 1),
(45, 1, 'CCCD khách hàng', 'uploads/docs/2024/08/HSTD2024080045_cccd.pdf', 'application/pdf', 2234567, 3, '2024-08-27 11:00:00', 1, 1),
(45, 2, 'Sao kê tài khoản', 'uploads/docs/2024/08/HSTD2024080045_statement.pdf', 'application/pdf', 4876543, 3, '2024-08-27 11:30:00', 1, 1),
-- Documents for in-progress applications
(16, 1, 'CCCD khách hàng', 'uploads/docs/2024/09/HSTD2024090016_cccd.pdf', 'application/pdf', 2345678, 2, '2024-09-15 14:00:00', 1, 1),
(17, 1, 'Giấy ĐKKD', 'uploads/docs/2024/09/HSTD2024090017_license.pdf', 'application/pdf', 3456789, 2, '2024-09-20 15:00:00', 1, 1),
(17, 2, 'Báo cáo tài chính', 'uploads/docs/2024/09/HSTD2024090017_financial.pdf', 'application/pdf', 7654321, 2, '2024-09-20 15:30:00', 1, 1),
(18, 1, 'CCCD', 'uploads/docs/2024/09/HSTD2024090018_cccd.pdf', 'application/pdf', 2456789, 3, '2024-09-22 12:00:00', 1, 1),
(19, 1, 'Giấy ĐKKD', 'uploads/docs/2024/09/HSTD2024090019_license.pdf', 'application/pdf', 3234567, 2, '2024-09-25 09:00:00', 1, 1),
(19, 3, 'Hợp đồng thương mại', 'uploads/docs/2024/09/HSTD2024090019_contract.pdf', 'application/pdf', 5678901, 2, '2024-09-25 09:30:00', 1, 1),
(20, 1, 'CCCD khách hàng', 'uploads/docs/2024/09/HSTD2024090020_cccd.pdf', 'application/pdf', 2345678, 4, '2024-09-28 10:00:00', 1, 1),
(20, 2, 'Sao kê ngân hàng', 'uploads/docs/2024/09/HSTD2024090020_statement.pdf', 'application/pdf', 4567890, 4, '2024-09-28 10:30:00', 1, 1),
-- Documents for applications needing more info
(96, 1, 'CCCD khách hàng', 'uploads/docs/2024/09/HSTD2024090096_cccd.pdf', 'application/pdf', 2123456, 2, '2024-09-17 10:00:00', 1, 1),
(96, 2, 'Báo cáo tài chính 2022 (cũ)', 'uploads/docs/2024/09/HSTD2024090096_financial_2022.pdf', 'application/pdf', 6543210, 2, '2024-09-17 10:30:00', 1, 0),
(97, 1, 'CCCD', 'uploads/docs/2024/09/HSTD2024090097_cccd.pdf', 'application/pdf', 2234567, 3, '2024-09-19 11:00:00', 1, 1),
(98, 1, 'CCCD khách hàng', 'uploads/docs/2024/09/HSTD2024090098_cccd.pdf', 'application/pdf', 2345678, 4, '2024-09-21 12:00:00', 1, 1),
(98, 2, 'Sao kê 3 tháng (chưa đủ)', 'uploads/docs/2024/09/HSTD2024090098_statement_3m.pdf', 'application/pdf', 3456789, 4, '2024-09-21 12:30:00', 1, 1);

-- ============================================================================
-- 8. APPLICATION COLLATERALS (Sample collaterals for applications)
-- ============================================================================

INSERT INTO `application_collaterals`
(`application_id`, `collateral_type_id`, `description`, `estimated_value`, `appraised_value`, `notes`, `warehouse_in`, `warehouse_in_date`, `warehouse_in_by_id`, `activated`, `activated_date`, `activated_by_id`) VALUES
-- Collaterals for approved applications (activated)
(41, 1, 'Nhà phố 3 tầng tại 123 Đường Láng, Đống Đa, Hà Nội, DT 80m2', 800000000.00, 750000000.00, 'Đã thẩm định giá, đã kích hoạt', 0, NULL, NULL, 1, '2024-08-19', 10),
(42, 3, 'Máy móc sản xuất nhựa công suất 500kg/h', 8000000000.00, 7500000000.00, 'Thiết bị mới 90%, đã kích hoạt', 1, '2024-08-22', 10, 1, '2024-08-24', 10),
(43, 1, 'Căn hộ chung cư 85m2 tại KĐT Ciputra, Tây Hồ, HN', 650000000.00, 600000000.00, 'Đã công chứng, đã kích hoạt', 0, NULL, NULL, 1, '2024-08-24', 10),
(44, 4, 'Hàng hóa xuất khẩu (điện tử) trị giá 5 tỷ', 5000000000.00, 4500000000.00, 'Hàng tồn kho xuất khẩu', 1, '2024-08-26', 10, 1, '2024-08-27', 10),
(45, 1, 'Nhà cấp 4 có gác lửng, DT 120m2, Ba Đình, HN', 900000000.00, 850000000.00, 'Vị trí đẹp, đã kích hoạt', 0, NULL, NULL, 1, '2024-08-29', 10),
(46, 1, 'Kho xưởng 500m2 tại KCN Thăng Long, HN', 15000000000.00, 14000000000.00, 'Kho xưởng lớn, đã thẩm định', 0, NULL, NULL, 1, '2024-08-31', 10),
(47, 2, 'Xe Toyota Fortuner 2023, màu trắng, biển 30A', 1200000000.00, 1100000000.00, 'Xe mới 95%, đã bảo hiểm', 0, NULL, NULL, 1, '2024-09-04', 10),
(48, 4, 'Hàng hóa tồn kho (vật liệu xây dựng)', 8000000000.00, 7000000000.00, 'Hàng tồn kho DN vận tải', 1, '2024-09-05', 10, 1, '2024-09-07', 10),
(49, 1, 'Nhà 4 tầng tại Quận 3, TP.HCM, DT 65m2', 1200000000.00, 1100000000.00, 'Nhà đẹp, khu trung tâm', 0, NULL, NULL, 1, '2024-09-09', 10),
(50, 1, 'Đất công nghiệp 2000m2 tại KCN Vsip Bắc Ninh', 12000000000.00, 11000000000.00, 'Đất sản xuất, vị trí tốt', 0, NULL, NULL, 1, '2024-09-11', 10),
(51, 2, 'Xe Honda CRV 2022, màu đen, biển 29A', 850000000.00, 800000000.00, 'Xe đẹp, chạy ít', 0, NULL, NULL, 1, '2024-09-14', 10),
(52, 4, 'Hàng hóa xuất khẩu (thủy sản)', 4000000000.00, 3500000000.00, 'Hàng đông lạnh xuất khẩu', 1, '2024-09-16', 10, 1, '2024-09-17', 10),
-- Collaterals for in-progress applications (some activated, some not)
(17, 3, 'Máy CNC 5 trục, hàng Nhật Bản', 9000000000.00, 8500000000.00, 'Thiết bị mới 85%, đã nhập kho chưa kích hoạt', 1, '2024-09-22', 10, 0, NULL, NULL),
(19, 4, 'Hàng hóa xuất khẩu (dệt may)', 3000000000.00, 2800000000.00, 'Đã nhập kho, chờ kích hoạt', 1, '2024-09-27', 10, 0, NULL, NULL),
(21, 1, 'Nhà 5 tầng tại Đống Đa, HN, DT 70m2', 1500000000.00, 1400000000.00, 'Đã thẩm định, chờ pháp lý hoàn tất', 0, NULL, NULL, 0, NULL, NULL),
(23, 1, 'Nhà xưởng 800m2 tại Hà Đông, HN', 18000000000.00, 16000000000.00, 'Đã thẩm định, chờ kích hoạt', 0, NULL, NULL, 0, NULL, NULL),
-- Collaterals for draft applications (not processed yet)
(1, 1, 'Nhà phố 2 tầng tại Đống Đa, HN, DT 60m2', 700000000.00, NULL, 'Chưa thẩm định giá', 0, NULL, NULL, 0, NULL, NULL),
(2, 3, 'Máy móc thiết bị sản xuất', 800000000.00, NULL, 'Chưa thẩm định', 0, NULL, NULL, 0, NULL, NULL),
(6, 1, 'Nhà xưởng KCN, DT 1200m2', 5000000000.00, NULL, 'Chưa thẩm định', 0, NULL, NULL, 0, NULL, NULL);

-- ============================================================================
-- END OF DEMO DATA
-- ============================================================================

