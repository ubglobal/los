-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 04:00 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `vnbc_los`
--
CREATE DATABASE IF NOT EXISTS `vnbc_los` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vnbc_los`;

--
-- Drop existing tables
--
DROP TABLE IF EXISTS `application_history`;
DROP TABLE IF EXISTS `application_documents`;
DROP TABLE IF EXISTS `application_repayment_sources`;
DROP TABLE IF EXISTS `application_collaterals`;
DROP TABLE IF EXISTS `customer_related_parties`;
DROP TABLE IF EXISTS `customer_credit_ratings`;
DROP TABLE IF EXISTS `credit_applications`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `collateral_types`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;


--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approval_limit` decimal(20,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `branch`, `approval_limit`) VALUES
(1, 'qhkh.an.nguyen', '$2y$10$5rsJyBB637YqJOTYSRPiY.SVEcdIwGZEs8Vy21GUz5cUEnO.Y3cmm', 'Nguyễn Văn An', 'CVQHKH', 'CN An Giang', NULL),
(2, 'thamdinh.lan.vu', '$2y$10$5rsJyBB637YqJOTYSRPiY.SVEcdIwGZEs8Vy21GUz5cUEnO.Y3cmm', 'Vũ Thị Lan', 'CVTĐ', 'Hội sở', NULL),
(3, 'pheduyet.hung.tran', '$2y$10$5rsJyBB637YqJOTYSRPiY.SVEcdIwGZEs8Vy21GUz5cUEnO.Y3cmm', 'Trần Mạnh Hùng', 'CPD', 'Hội sở', '5000000000.00'),
(4, 'admin', '$2y$10$5rsJyBB637YqJOTYSRPiY.SVEcdIwGZEs8Vy21GUz5cUEnO.Y3cmm', 'Quản trị viên', 'Admin', 'Hội sở', NULL),
(5, 'gd.khoi.nguyen', '$2y$10$5rsJyBB637YqJOTYSRPiY.SVEcdIwGZEs8Vy21GUz5cUEnO.Y3cmm', 'Nguyễn Minh Khôi', 'GDK', 'Hội sở', '20000000000.00');


--
-- Table structure for table `customers`
--
CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_type` enum('CÁ NHÂN','DOANH NGHIỆP') COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_tax_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_representative` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `customer_code`, `customer_type`, `full_name`, `id_number`, `dob`, `address`, `phone_number`, `email`, `company_tax_code`, `company_representative`) VALUES
(1, 'CN0001', 'CÁ NHÂN', 'Nguyễn Thu Trang', '038091001234', '1991-05-20', 'Số 10, ngõ 20, đường Xuân Thủy, Cầu Giấy, Hà Nội', '0912345678', 'trang.nt@email.com', NULL, NULL),
(2, 'DN0001', 'DOANH NGHIỆP', 'Công ty TNHH An Phát', NULL, NULL, 'Lô A2, KCN Thăng Long, Đông Anh, Hà Nội', '02438812345', 'info@anphat.com.vn', '0101234567', 'Trần Văn Mạnh'),
(3, 'CN0002', 'CÁ NHÂN', 'Phạm Thị Lan Anh', '017095009876', '1995-02-10', 'Số 55, đường Nguyễn Trãi, Thanh Xuân, Hà Nội', '0905112233', 'lananh.pt@email.com', NULL, NULL),
(4, 'CN0003', 'CÁ NHÂN', 'Hoàng Văn Hải', '035085001122', '1985-09-30', 'Thôn 3, xã Song Phương, Hoài Đức, Hà Nội', '0978123456', 'hai.hv@email.com', NULL, NULL),
(5, 'CN0004', 'CÁ NHÂN', 'Vũ Đức Thắng', '022090003344', '1990-07-25', 'Số 123, đường Lê Lợi, TP. Hải Dương, Hải Dương', '0945987654', 'thang.vd@email.com', NULL, NULL),
(6, 'DN0002', 'DOANH NGHIỆP', 'Công ty CP Bình Minh', NULL, NULL, 'Số 18, đường Giải Phóng, Đống Đa, Hà Nội', '02438686868', 'contact@binhminhcorp.vn', '0109876543', 'Lê Thị Hòa'),
(7, 'CN0005', 'CÁ NHÂN', 'Trần Thị Bích', '001195005678', '1995-08-18', 'Số 5, đường Giải Phóng, Hai Bà Trưng, Hà Nội', '0903456789', 'bich.tt@email.com', NULL, NULL),
(8, 'DN0003', 'DOANH NGHIỆP', 'Tập đoàn Xây dựng Hòa Bình', NULL, NULL, 'Tầng 25, Tòa nhà CEO, Phạm Hùng, Hà Nội', '02437875888', 'contact@hoabinh.com.vn', '0301234567', 'Lê Viết Hải'),
(9, 'CN0006', 'CÁ NHÂN', 'Trần Văn Kiên', '027088001234', '1988-06-14', 'Số 45, phố Huế, Hai Bà Trưng, Hà Nội', '0988888888', 'kien.tv@email.com', NULL, NULL),
(10, 'CN0007', 'CÁ NHÂN', 'Trần Văn Mạnh', '038085004321', '1985-01-15', 'Lô A2, KCN Thăng Long, Đông Anh, Hà Nội', '0918765432', 'manh.tv@anphat.com.vn', NULL, NULL),
(11, 'CN1011', 'CÁ NHÂN', 'Lê Minh Anh', '045092001235', '1992-03-12', '12B, Đường 3/2, Quận 10, TP.HCM', '0912345679', 'minhanh.le@email.com', NULL, NULL),
(12, 'CN1012', 'CÁ NHÂN', 'Phạm Hoài Nam', '046088004567', '1988-11-25', '256, Nguyễn Thị Minh Khai, Quận 3, TP.HCM', '0903456780', 'hoainam.pham@email.com', NULL, NULL),
(13, 'DN1013', 'DOANH NGHIỆP', 'Công ty TNHH Vận Tải Sài Gòn', NULL, NULL, '345, Xô Viết Nghệ Tĩnh, Bình Thạnh, TP.HCM', '02838991234', 'contact@saigontrans.vn', '0301234568', 'Nguyễn Văn Hùng'),
(14, 'CN1014', 'CÁ NHÂN', 'Võ Thị Thanh Thúy', '048095007890', '1995-07-01', '789, Lê Văn Sỹ, Quận Tân Bình, TP.HCM', '0987654321', 'thuy.vo@email.com', NULL, NULL),
(15, 'CN1015', 'CÁ NHÂN', 'Đặng Ngọc Long', '049080001123', '1980-01-15', '101, Pasteur, Quận 1, TP.HCM', '0908123456', 'long.dang@email.com', NULL, NULL),
(16, 'DN1016', 'DOANH NGHIỆP', 'Công ty CP Dệt May Phong Phú', NULL, NULL, 'Khu Công Nghiệp Tân Tạo, Bình Tân, TP.HCM', '02837540123', 'info@phongphu.com.vn', '0309876544', 'Trần Thị Mai'),
(17, 'CN1017', 'CÁ NHÂN', 'Hồ Thị Kim Chi', '051093004455', '1993-09-09', '45, Nguyễn Văn Cừ, Quận 5, TP.HCM', '0918234567', 'chi.ho@email.com', NULL, NULL),
(18, 'CN1018', 'CÁ NHÂN', 'Ngô Bá Khá', '052089007788', '1989-12-30', '88, Võ Văn Tần, Quận 3, TP.HCM', '0938456789', 'kha.ngo@email.com', NULL, NULL),
(19, 'DN1019', 'DOANH NGHIỆP', 'Công ty TNHH Thực Phẩm An Toàn Việt', NULL, NULL, 'Lô B3, KCN Hiệp Phước, Nhà Bè, TP.HCM', '02837819999', 'info@vietsafe.vn', '0305556677', 'Phan Thanh Bình'),
(20, 'CN1020', 'CÁ NHÂN', 'Dương Văn Tùng', '054091009900', '1991-04-18', '222, Điện Biên Phủ, Quận 10, TP.HCM', '0909654321', 'tung.duong@email.com', NULL, NULL),
(21, 'CN1021', 'CÁ NHÂN', 'Mai Thị Hoa', '031085001236', '1985-06-22', '33, Hai Bà Trưng, Hoàn Kiếm, Hà Nội', '0912345680', 'hoa.mai@email.com', NULL, NULL),
(22, 'DN1022', 'DOANH NGHIỆP', 'Công ty TNHH Xây Dựng Delta', NULL, NULL, 'Tầng 10, Tòa nhà Sudico, Mễ Trì, Hà Nội', '02437878888', 'info@delta.com.vn', '0100101102', 'Nguyễn Minh Tuấn'),
(23, 'CN1023', 'CÁ NHÂN', 'Lý Văn Dũng', '033090004568', '1990-08-14', '77, Láng Hạ, Đống Đa, Hà Nội', '0903456781', 'dung.ly@email.com', NULL, NULL),
(24, 'CN1024', 'CÁ NHÂN', 'Bùi Thị Hà', '034094007891', '1994-10-05', '99, Trần Duy Hưng, Cầu Giấy, Hà Nội', '0987654322', 'ha.bui@email.com', NULL, NULL),
(25, 'CN1025', 'CÁ NHÂN', 'Đỗ Tiến Mạnh', '036082001124', '1982-02-28', '15, Ngô Quyền, Hoàn Kiếm, Hà Nội', '0908123457', 'manh.do@email.com', NULL, NULL),
(26, 'DN1026', 'DOANH NGHIỆP', 'Công ty CP FPT', NULL, NULL, 'Tòa nhà FPT, 17 Duy Tân, Cầu Giấy, Hà Nội', '02473007300', 'support@fpt.com.vn', '0101248141', 'Trương Gia Bình'),
(27, 'CN1027', 'CÁ NHÂN', 'Nguyễn Thị Kim Liên', '037096004456', '1996-11-11', '24, Kim Mã, Ba Đình, Hà Nội', '0918234568', 'lien.nguyen@email.com', NULL, NULL),
(28, 'CN1028', 'CÁ NHÂN', 'Trịnh Xuân Hùng', '038089007789', '1989-03-03', '56, Nguyễn Chí Thanh, Đống Đa, Hà Nội', '0938456780', 'hung.trinh@email.com', NULL, NULL),
(29, 'DN1029', 'DOANH NGHIỆP', 'Ngân hàng TMCP Kỹ Thương Việt Nam', NULL, NULL, '191 Bà Triệu, Hai Bà Trưng, Hà Nội', '1800588822', 'call_center@techcombank.com.vn', '0100230800', 'Hồ Hùng Anh'),
(30, 'CN1030', 'CÁ NHÂN', 'Đào Quang Vinh', '040091009901', '1991-05-19', '88, Láng Hạ, Đống Đa, Hà Nội', '0909654322', 'vinh.dao@email.com', NULL, NULL),
(31, 'CN1031', 'CÁ NHÂN', 'Nguyễn Văn Bình', '056087001237', '1987-07-17', '11, Trần Hưng Đạo, Hoàn Kiếm, Hà Nội', '0912345681', 'binh.nguyen@email.com', NULL, NULL),
(32, 'DN1032', 'DOANH NGHIỆP', 'Công ty TNHH Dược Phẩm Tâm Bình', NULL, NULL, '349 Kim Mã, Ba Đình, Hà Nội', '02437245678', 'info@tambinh.vn', '0101347071', 'Lê Thị Bình'),
(33, 'CN1033', 'CÁ NHÂN', 'Hoàng Thị Thu', '058093004569', '1993-01-23', '45, Cát Linh, Đống Đa, Hà Nội', '0903456782', 'thu.hoang@email.com', NULL, NULL),
(34, 'CN1034', 'CÁ NHÂN', 'Vũ Văn Nam', '060086007892', '1986-04-16', '12, Tôn Đức Thắng, Đống Đa, Hà Nội', '0987654323', 'nam.vu@email.com', NULL, NULL),
(35, 'CN1035', 'CÁ NHÂN', 'Trần Minh Đức', '062090001125', '1990-09-02', '111, Xã Đàn, Đống Đa, Hà Nội', '0908123458', 'duc.tran@email.com', NULL, NULL),
(36, 'DN1036', 'DOANH NGHIỆP', 'Công ty CP Sữa Việt Nam Vinamilk', NULL, NULL, 'Số 10, Tân Trào, Tân Phú, Quận 7, TP.HCM', '02854155555', 'vinamilk@vinamilk.com.vn', '0300588569', 'Mai Kiều Liên'),
(37, 'CN1037', 'CÁ NHÂN', 'Lê Thị Ngọc', '064095004457', '1995-12-08', '234, Tây Sơn, Đống Đa, Hà Nội', '0918234569', 'ngoc.le@email.com', NULL, NULL),
(38, 'CN1038', 'CÁ NHÂN', 'Phan Văn Hoàng', '066088007780', '1988-08-24', '78, Chùa Bộc, Đống Đa, Hà Nội', '0938456781', 'hoang.phan@email.com', NULL, NULL),
(39, 'DN1039', 'DOANH NGHIỆP', 'Công ty CP VNG', NULL, NULL, 'Khu Chế Xuất Tân Thuận, Quận 7, TP.HCM', '02838238888', 'contact@vng.com.vn', '0303492143', 'Lê Hồng Minh'),
(40, 'CN1040', 'CÁ NHÂN', 'Ngô Thị Thảo', '068092009902', '1992-02-14', '90, Thái Hà, Đống Đa, Hà Nội', '0909654323', 'thao.ngo@email.com', NULL, NULL),
(41, 'CN1041', 'CÁ NHÂN', 'Đinh Thị Yến', '070084001238', '1984-10-10', '44, Lý Thường Kiệt, Hoàn Kiếm, Hà Nội', '0912345682', 'yen.dinh@email.com', NULL, NULL),
(42, 'DN1042', 'DOANH NGHIỆP', 'Công ty TNHH Bảo Hiểm Prudential', NULL, NULL, 'Tầng 25, Keangnam, Phạm Hùng, Hà Nội', '18006600', 'customer.service@prudential.com.vn', '0100572459', 'Phương Tiến Minh'),
(43, 'CN1043', 'CÁ NHÂN', 'Lưu Quang Vũ', '072091004560', '1991-03-29', '66, Bà Triệu, Hoàn Kiếm, Hà Nội', '0903456783', 'vu.luu@email.com', NULL, NULL),
(44, 'CN1044', 'CÁ NHÂN', 'Nguyễn Hoàng Yến', '074097007893', '1997-05-07', '12, Hàng Bài, Hoàn Kiếm, Hà Nội', '0987654324', 'yen.nguyenhoang@email.com', NULL, NULL),
(45, 'CN1045', 'CÁ NHÂN', 'Vương Đình Dũng', '075083001126', '1983-11-30', '25, Phan Chu Trinh, Hoàn Kiếm, Hà Nội', '0908123459', 'dung.vuong@email.com', NULL, NULL),
(46, 'DN1046', 'DOANH NGHIỆP', 'Công ty CP Thế Giới Di Động', NULL, NULL, 'KCN Sóng Thần 2, Dĩ An, Bình Dương', '18001060', 'cskh@thegioididong.com', '0306731335', 'Nguyễn Đức Tài'),
(47, 'CN1047', 'CÁ NHÂN', 'Đoàn Thị Hương', '077096004458', '1996-07-21', '8, Tràng Tiền, Hoàn Kiếm, Hà Nội', '0918234560', 'huong.doan@email.com', NULL, NULL),
(48, 'CN1048', 'CÁ NHÂN', 'Bùi Anh Tuấn', '079088007781', '1988-09-09', '30, Hàng Khay, Hoàn Kiếm, Hà Nội', '0938456782', 'tuan.bui@email.com', NULL, NULL),
(49, 'DN1049', 'DOANH NGHIỆP', 'Công ty TNHH Grab Việt Nam', NULL, NULL, 'Tầng 2, 268 Tô Hiến Thành, Quận 10, TP.HCM', '02871087108', 'support.vn@grab.com', '0312650437', 'Nguyễn Thái Hải Vân'),
(50, 'CN1050', 'CÁ NHÂN', 'Lê Hồng Phong', '080092009903', '1992-06-06', '18, Điện Biên Phủ, Ba Đình, Hà Nội', '0909654324', 'phong.le@email.com', NULL, NULL),
(51, 'CN1051', 'CÁ NHÂN', 'Nguyễn Đức Anh', '082087001239', '1987-08-08', '34, Quán Thánh, Ba Đình, Hà Nội', '0912345683', 'ducanh.nguyen@email.com', NULL, NULL),
(52, 'DN1052', 'DOANH NGHIỆP', 'Công ty TNHH Shopee Việt Nam', NULL, NULL, 'Tầng 28, Saigon Centre, 67 Lê Lợi, Quận 1', '19001221', 'support@shopee.vn', '0106773783', 'Trần Tuấn Anh'),
(53, 'CN1053', 'CÁ NHÂN', 'Trần Thị Thu Hà', '084093004561', '1993-02-18', '55, Phan Đình Phùng, Ba Đình, Hà Nội', '0903456784', 'ha.tran@email.com', NULL, NULL),
(54, 'CN1054', 'CÁ NHÂN', 'Phạm Văn Đồng', '086086007894', '1986-05-26', '76, Hoàng Diệu, Ba Đình, Hà Nội', '0987654325', 'dong.pham@email.com', NULL, NULL),
(55, 'CN1055', 'CÁ NHÂN', 'Lý Thu Thảo', '088090001127', '1990-10-12', '99, Đội Cấn, Ba Đình, Hà Nội', '0908123450', 'thao.ly@email.com', NULL, NULL),
(56, 'DN1056', 'DOANH NGHIỆP', 'Tập đoàn Vingroup', NULL, NULL, 'Số 7, đường Bằng Lăng 1, KĐT Vinhomes Riverside, Long Biên', '02439749999', 'info@vingroup.net', '0101245486', 'Phạm Nhật Vượng'),
(57, 'CN1057', 'CÁ NHÂN', 'Mai Thế Anh', '090095004459', '1995-01-01', '11, Ngọc Hà, Ba Đình, Hà Nội', '0918234561', 'anh.mai@email.com', NULL, NULL),
(58, 'CN1058', 'CÁ NHÂN', 'Đặng Thuỳ Trang', '092088007782', '1988-11-18', '22, Lê Hồng Phong, Ba Đình, Hà Nội', '0938456783', 'trang.dang@email.com', NULL, NULL),
(59, 'DN1059', 'DOANH NGHIỆP', 'Công ty CP Hàng không Vietjet', NULL, NULL, '302/3 Kim Mã, Ba Đình, Hà Nội', '19001886', '19001886@vietjetair.com', '0102325399', 'Nguyễn Thị Phương Thảo'),
(60, 'CN1060', 'CÁ NHÂN', 'Vũ Hải Đăng', '094092009904', '1992-07-27', '45, Giang Văn Minh, Ba Đình, Hà Nội', '0909654325', 'dang.vu@email.com', NULL, NULL),
(61, 'CN1061', 'CÁ NHÂN', 'Hoàng Lê Vy', '096087001230', '1987-12-12', '18, Lý Nam Đế, Hoàn Kiếm, Hà Nội', '0912345684', 'vy.hoang@email.com', NULL, NULL),
(62, 'DN1062', 'DOANH NGHIỆP', 'Tổng Công ty Viễn thông Mobifone', NULL, NULL, 'Lô VP1, Yên Hòa, Cầu Giấy, Hà Nội', '18001090', 'cskh@mobifone.vn', '0100686209', 'Tô Mạnh Cường'),
(63, 'CN1063', 'CÁ NHÂN', 'Nguyễn Thị Minh', '098093004562', '1993-03-30', '35, Trần Phú, Ba Đình, Hà Nội', '0903456785', 'minh.nguyenthi@email.com', NULL, NULL),
(64, 'CN1064', 'CÁ NHÂN', 'Lê Văn Luyện', '100086007895', '1986-06-20', '58, Hàng Bông, Hoàn Kiếm, Hà Nội', '0987654326', 'luyen.le@email.com', NULL, NULL),
(65, 'CN1065', 'CÁ NHÂN', 'Phan Thị Thanh', '102090001128', '1990-11-15', '70, Hàng Gai, Hoàn Kiếm, Hà Nội', '0908123451', 'thanh.phan@email.com', NULL, NULL),
(66, 'DN1066', 'DOANH NGHIỆP', 'Tập đoàn Viễn thông Quân đội Viettel', NULL, NULL, 'Lô D26, Yên Hòa, Cầu Giấy, Hà Nội', '18008098', 'cskh@viettel.com.vn', '0100109106', 'Tào Đức Thắng'),
(67, 'CN1067', 'CÁ NHÂN', 'Đỗ Thị Lan', '104095004450', '1995-02-02', '92, Hàng Đào, Hoàn Kiếm, Hà Nội', '0918234562', 'lan.do@email.com', NULL, NULL),
(68, 'CN1068', 'CÁ NHÂN', 'Trần Quốc Tuấn', '106088007783', '1988-12-25', '105, Hàng Bạc, Hoàn Kiếm, Hà Nội', '0938456784', 'tuan.tran@email.com', NULL, NULL),
(69, 'DN1069', 'DOANH NGHIỆP', 'Công ty CP Bán lẻ Kỹ thuật số FPT', NULL, NULL, '261-263 Khánh Hội, Quận 4, TP.HCM', '18006601', 'fptshop@fpt.com.vn', '0102173292', 'Hoàng Trung Kiên'),
(70, 'CN1070', 'CÁ NHÂN', 'Võ Hoài Linh', '108092009905', '1992-08-19', '118, Hàng Buồm, Hoàn Kiếm, Hà Nội', '0909654326', 'linh.vo@email.com', NULL, NULL),
(71, 'CN1071', 'CÁ NHÂN', 'Phạm Nhật Vượng', '0100108081', '1968-08-05', 'Số 7 Bằng Lăng 1, KĐT Vinhomes Riverside', '0913540000', 'pnv@vingroup.net', NULL, NULL),
(72, 'CN1072', 'CÁ NHÂN', 'Nguyễn Thị Phương Thảo', '0102325399', '1970-06-07', '302/3 Kim Mã, Ba Đình, Hà Nội', '0903888999', 'thaonguyen@vietjetair.com', NULL, NULL),
(73, 'CN1073', 'CÁ NHÂN', 'Trần Đình Long', '0200105315', '1961-02-22', 'Khu đô thị sinh thái Ecopark, Hưng Yên', '0913233000', 'longtd@hoaphat.com.vn', NULL, NULL),
(74, 'CN1074', 'CÁ NHÂN', 'Hồ Hùng Anh', '0100230800', '1970-06-08', '191 Bà Triệu, Hai Bà Trưng, Hà Nội', '0903405577', 'hungho@techcombank.com.vn', NULL, NULL),
(75, 'CN1075', 'CÁ NHÂN', 'Nguyễn Đăng Quang', '0101298822', '1963-08-23', 'Tầng 12, Tòa nhà Capital, 41 Hai Bà Trưng', '0913388888', 'quangnd@masangroup.com', NULL, NULL),
(76, 'DN1076', 'DOANH NGHIỆP', 'Công ty CP Tập đoàn Hòa Phát', NULL, NULL, 'KCN Phố Nối A, Hưng Yên', '02213942588', 'info@hoaphat.com.vn', '0900185972', 'Trần Đình Long'),
(77, 'DN1077', 'DOANH NGHIỆP', 'Công ty CP Tập đoàn Masan', NULL, NULL, 'Tầng 8, Central Plaza, 17 Lê Duẩn, Quận 1', '02862563862', 'info@masangroup.com', '0303576603', 'Nguyễn Đăng Quang'),
(78, 'CN1078', 'CÁ NHÂN', 'Nguyễn Văn Hùng', '0301234568', '1978-05-15', '345 Xô Viết Nghệ Tĩnh, Bình Thạnh', '0903123123', 'hung.nv@saigontrans.vn', NULL, NULL),
(79, 'CN1079', 'CÁ NHÂN', 'Trần Thị Mai', '0309876544', '1980-09-20', 'KCN Tân Tạo, Bình Tân', '0908789789', 'mai.tt@phongphu.com.vn', NULL, NULL),
(80, 'CN1080', 'CÁ NHÂN', 'Phan Thanh Bình', '0305556677', '1982-01-10', 'Lô B3, KCN Hiệp Phước, Nhà Bè', '0913987987', 'binh.pt@vietsafe.vn', NULL, NULL),
(81, 'CN1081', 'CÁ NHÂN', 'Lê Thị Hòa', '0109876543', '1975-04-12', 'Số 18, đường Giải Phóng, Đống Đa, Hà Nội', '0913223344', 'hoa.lt@binhminhcorp.vn', NULL, NULL),
(82, 'CN1082', 'CÁ NHÂN', 'Lê Viết Hải', '0301234567', '1958-11-25', 'Tầng 25, Tòa nhà CEO, Phạm Hùng, Hà Nội', '0903336688', 'hai.lv@hoabinh.com.vn', NULL, NULL),
(83, 'DN1083', 'DOANH NGHIỆP', 'Công ty CP Đầu tư và Công nghệ BE GROUP', NULL, NULL, 'Tầng 16, Sai Gon Tower, 29 Lê Duẩn, Quận 1', '1900232345', 'hotro@be.com.vn', '0108249247', 'Vũ Hoàng Yến'),
(84, 'DN1084', 'DOANH NGHIỆP', 'Công ty TNHH Baemin Việt Nam', NULL, NULL, 'Tầng 1, Tòa nhà Saigon Paragon, 3 Nguyễn Lương Bằng, Quận 7', '19003090', 'hotro@baemin.vn', '0315729906', 'Jinwoo Song'),
(85, 'DN1085', 'DOANH NGHIỆP', 'Công ty TNHH MoMo', NULL, NULL, 'Tầng M, Tòa nhà Petroland, 12 Tân Trào, Quận 7', '1900545441', 'hotro@momo.vn', '0305289153', 'Phạm Thành Đức'),
(86, 'CN1086', 'CÁ NHÂN', 'Nguyễn Hòa Bình', '0102189937', '1981-05-25', 'Tầng 17, Center Building, 85 Vũ Trọng Phụng', '0913588666', 'binh.nh@nexttech.asia', NULL, NULL),
(87, 'DN1087', 'DOANH NGHIỆP', 'Công ty CP NextTech', NULL, NULL, 'Tầng 17, Center Building, 85 Vũ Trọng Phụng, Thanh Xuân', '02473030303', 'contact@nexttech.asia', '0102189937', 'Nguyễn Hòa Bình'),
(88, 'CN1088', 'CÁ NHÂN', 'Phạm Đình Đoàn', '0100778181', '1964-01-01', 'Tập đoàn Phú Thái, 10 Phan Văn Trường, Cầu Giấy', '0903403333', 'doan.pd@phuthaigroup.com', NULL, NULL),
(89, 'DN1089', 'DOANH NGHIỆP', 'Tập đoàn Phú Thái', NULL, NULL, '10 Phan Văn Trường, Dịch Vọng Hậu, Cầu Giấy', '02437921333', 'info@phuthaigroup.com', '0100778181', 'Phạm Đình Đoàn'),
(90, 'CN1090', 'CÁ NHÂN', 'Nguyễn Bạch Điệp', '0302235941', '1972-01-01', '261-263 Khánh Hội, Quận 4, TP.HCM', '0903800900', 'diep.nb@fpt.com.vn', NULL, NULL),
(91, 'CN1091', 'CÁ NHÂN', 'Nguyễn Mạnh Tường', '0305289153', '1980-01-01', 'Tòa nhà MoMo, 12 Tân Trào, Quận 7', '0903999999', 'tuong.nm@momo.vn', NULL, NULL),
(92, 'CN1092', 'CÁ NHÂN', 'Lê Hồng Minh', '0303492143', '1977-01-01', 'Khu Chế Xuất Tân Thuận, Quận 7, TP.HCM', '0903111222', 'minh.lh@vng.com.vn', NULL, NULL),
(93, 'CN1093', 'CÁ NHÂN', 'Nguyễn Đức Tài', '0306731335', '1969-01-01', 'KCN Sóng Thần 2, Dĩ An, Bình Dương', '0903333333', 'tai.nd@thegioididong.com', NULL, NULL),
(94, 'CN1094', 'CÁ NHÂN', 'Trương Gia Bình', '0101248141', '1956-05-19', 'Tòa nhà FPT, 17 Duy Tân, Cầu Giấy, Hà Nội', '0903404151', 'binh.tg@fpt.com.vn', NULL, NULL),
(95, 'CN1095', 'CÁ NHÂN', 'Đỗ Anh Tuấn', '0102671018', '1975-01-01', 'Tòa nhà Sunshine Center, 16 Phạm Hùng, Hà Nội', '0988999999', 'tuan.da@sunshinegroup.vn', NULL, NULL),
(96, 'DN1096', 'DOANH NGHIỆP', 'Công ty CP Tập đoàn Sunshine', NULL, NULL, 'Tòa nhà Sunshine Center, 16 Phạm Hùng, Nam Từ Liêm', '19006936', 'info@sunshinegroup.vn', '0102671018', 'Đỗ Anh Tuấn'),
(97, 'CN1097', 'CÁ NHÂN', 'Nguyễn Thị Nga', '0100110339', '1955-08-17', 'Tập đoàn BRG, 18 Lý Thường Kiệt, Hoàn Kiếm', '0903408888', 'nga.nt@brggroup.vn', NULL, NULL),
(98, 'DN1098', 'DOANH NGHIỆP', 'Tập đoàn BRG', NULL, NULL, '18 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội', '02439366366', 'info@brggroup.vn', '0100110339', 'Nguyễn Thị Nga'),
(99, 'CN1099', 'CÁ NHÂN', 'Thái Hương', '0102191986', '1958-10-12', 'Tập đoàn TH, 166 Nguyễn Thái Học, Ba Đình', '0913556677', 'huong.thai@thmilk.vn', NULL, NULL),
(100, 'DN1100', 'DOANH NGHIỆP', 'Công ty CP Sữa TH', NULL, NULL, '166 Nguyễn Thái Học, Ba Đình, Hà Nội', '1800545440', 'contact@thmilk.vn', '0102191986', 'Thái Hương'),
(101, 'CN1101', 'CÁ NHÂN', 'Mai Kiều Liên', '0300588569', '1953-09-01', 'Số 10, Tân Trào, Tân Phú, Quận 7, TP.HCM', '0903444555', 'lien.mk@vinamilk.com.vn', NULL, NULL);


--
-- Table structure for table `products` and `collateral_types`
--
CREATE TABLE `products` ( `id` int(11) NOT NULL, `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `products` (`id`, `name`, `description`) VALUES (1, 'Vay mua ô tô', 'Sản phẩm cho vay khách hàng cá nhân mua xe ô tô.'), (2, 'Vay bổ sung vốn kinh doanh', 'Sản phẩm cho vay doanh nghiệp bổ sung vốn lưu động ngắn hạn.'), (3, 'Vay cầm cố GTCG', 'Sản phẩm cho vay nhanh dựa trên tài sản là giấy tờ có giá.');
CREATE TABLE `collateral_types` ( `id` int(11) NOT NULL, `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `collateral_types` (`id`, `name`) VALUES (1, 'Bất động sản'), (2, 'Ô tô hình thành từ vốn vay'), (3, 'Giấy tờ có giá (Sổ tiết kiệm)');

--
-- Table structure for table `credit_applications`
--
CREATE TABLE `credit_applications` ( `id` int(11) NOT NULL, `hstd_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL, `customer_id` int(11) NOT NULL, `amount` decimal(20,2) NOT NULL, `purpose` text COLLATE utf8mb4_unicode_ci NOT NULL, `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL, `stage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL, `assigned_to_id` int(11) DEFAULT NULL, `created_by_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `credit_applications` (`id`, `hstd_code`, `customer_id`, `amount`, `purpose`, `status`, `stage`, `assigned_to_id`, `created_by_id`, `product_id`) VALUES
(1, 'CAR.2024.1001', 1, '700000000.00', 'Vay mua xe Toyota Vios', 'Đang xử lý', 'Chờ thẩm định', 2, 1, 1),
(3, 'CAR.2024.1003', 3, '1200000000.00', 'Vay mua xe Ford Everest', 'Đang xử lý', 'Chờ phê duyệt', 3, 1, 1),
(4, 'CAR.2024.1004', 4, '500000000.00', 'Vay mua xe Hyundai Accent', 'Đã phê duyệt', 'Đã phê duyệt', NULL, 1, 1),
(5, 'CAR.2024.1005', 5, '800000000.00', 'Vay mua xe Honda CRV', 'Đã từ chối', 'Đã từ chối', NULL, 1, 1),
(6, 'CORP.2024.2001', 2, '2000000000.00', 'Bổ sung vốn lưu động nhập hàng tiêu dùng', 'Đang xử lý', 'Chờ thẩm định', 2, 1, 2),
(7, 'CORP.2024.2002', 6, '5000000000.00', 'Thanh toán tiền hàng cho nhà cung cấp', 'Đang xử lý', 'Chờ phê duyệt', 3, 1, 2),
(11, 'PLEDGE.2024.3001', 7, '150000000.00', 'Vay tiêu dùng cá nhân', 'Đang xử lý', 'Chờ phê duyệt', 3, 1, 3),
(16, 'CORP.2024.2006', 8, '15000000000.00', 'Bảo lãnh thực hiện hợp đồng xây dựng dự án ABC', 'Đang xử lý', 'Chờ phê duyệt cấp cao', 5, 1, 2),
(17, 'CAR.2024.1006', 9, '650000000.00', 'Vay mua xe Mitsubishi Xpander', 'Đang xử lý', 'Yêu cầu bổ sung', 1, 1, 1);

--
-- Table structure for table `customer_credit_ratings`
--
CREATE TABLE `customer_credit_ratings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating_type` enum('NỘI BỘ','BÊN NGOÀI') COLLATE utf8mb4_unicode_ci NOT NULL,
  `organization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `customer_credit_ratings` (`id`, `customer_id`, `rating_type`, `organization`, `rating`, `rating_date`) VALUES
(1, 1, 'NỘI BỘ', 'U&Bank', 'A', '2024-05-10'),
(2, 2, 'NỘI BỘ', 'U&Bank', 'AA', '2024-03-15'),
(3, 2, 'BÊN NGOÀI', 'FiinRatings', 'BBB+', '2024-01-20'),
(4, 3, 'NỘI BỘ', 'U&Bank', 'B', '2024-04-22'),
(5, 6, 'NỘI BỘ', 'U&Bank', 'A+', '2024-02-01'),
(6, 8, 'BÊN NGOÀI', 'S&P', 'BB', '2023-12-30');

--
-- Table structure for table `customer_related_parties`
--
CREATE TABLE `customer_related_parties` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `related_customer_id` int(11) NOT NULL,
  `relationship_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `customer_related_parties` (`id`, `customer_id`, `related_customer_id`, `relationship_type`) VALUES
(1, 2, 10, 'Người đại diện theo pháp luật'),
(2, 10, 2, 'Là người đại diện của');

--
-- Tables for `application_collaterals`, `documents`, `repayment`, `history`
--
CREATE TABLE `application_collaterals` ( `id` int(11) NOT NULL, `application_id` int(11) NOT NULL, `collateral_type_id` int(11) NOT NULL, `description` text COLLATE utf8mb4_unicode_ci NOT NULL, `value` decimal(20,2) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `application_collaterals` (`id`, `application_id`, `collateral_type_id`, `description`, `value`) VALUES (1, 1, 2, 'Xe ô tô Toyota Vios hình thành từ vốn vay', '900000000.00'), (2, 3, 1, 'Bất động sản tại số 55 Nguyễn Trãi', '3000000000.00'), (3, 4, 2, 'Xe ô tô Hyundai Accent', '650000000.00'), (4, 6, 1, 'Nhà xưởng tại KCN Thăng Long', '5000000000.00'), (5, 11, 3, 'Sổ tiết kiệm số 123456789', '200000000.00');

CREATE TABLE `application_documents` ( `id` int(11) NOT NULL, `application_id` int(11) NOT NULL, `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `uploaded_by_id` int(11) NOT NULL, `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `application_documents` (`id`, `application_id`, `document_name`, `file_path`, `uploaded_by_id`) VALUES (1, 4, 'Hop_dong_mua_ban_xe.pdf', 'sample_doc.pdf', 1), (2, 4, 'Sao_ke_luong.pdf', 'sample_doc.pdf', 1);

CREATE TABLE `application_repayment_sources` ( `id` int(11) NOT NULL, `application_id` int(11) NOT NULL, `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL, `monthly_income` decimal(20,2) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `application_repayment_sources` (`id`, `application_id`, `source_type`, `description`, `monthly_income`) VALUES (1, 1, 'Lương', 'Lương từ công ty ABC', '30000000.00'), (2, 6, 'Lợi nhuận kinh doanh', 'Lợi nhuận từ hoạt động kinh doanh', '300000000.00');

CREATE TABLE `application_history` ( `id` int(11) NOT NULL, `application_id` int(11) NOT NULL, `user_id` int(11) NOT NULL, `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL, `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `application_history` (`id`, `application_id`, `user_id`, `action`, `comment`) VALUES (1, 1, 1, 'Gửi đi', 'Đã khởi tạo, đề nghị thẩm định.'),(3, 3, 1, 'Gửi đi', 'KH tiềm năng.'),(4, 4, 1, 'Gửi đi', 'Khởi tạo.'),(5, 4, 2, 'Trình duyệt', 'Đã thẩm định, OK.'),(6, 4, 3, 'Phê duyệt', 'Đồng ý.'),(7, 5, 1, 'Gửi đi', 'Khởi tạo.'),(8, 5, 2, 'Trình duyệt', 'Nguồn thu không rõ ràng.'),(9, 5, 3, 'Từ chối', 'Không đủ khả năng trả nợ.'),(10, 6, 1, 'Gửi đi', 'Hồ sơ DN An Phát.'),(13, 11, 1, 'Gửi đi', 'Vay cầm cố.'),(14, 11, 2, 'Trình duyệt', 'Rủi ro thấp.'),(18, 16, 1, 'Gửi đi', 'KH VIP.'),(19, 16, 2, 'Trình duyệt cấp cao', 'Vượt hạn mức CPD, trình GĐK.'),(20, 17, 1, 'Gửi đi', 'Khởi tạo hồ sơ.'),(21, 17, 2, 'Yêu cầu bổ sung', 'Bổ sung sao kê lương.');

--
-- Indexes for tables
--
ALTER TABLE `users` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `customers` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `customer_code` (`customer_code`);
ALTER TABLE `products` ADD PRIMARY KEY (`id`);
ALTER TABLE `collateral_types` ADD PRIMARY KEY (`id`);
ALTER TABLE `credit_applications` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `hstd_code` (`hstd_code`), ADD KEY `fk_app_customer` (`customer_id`), ADD KEY `fk_app_assigned` (`assigned_to_id`), ADD KEY `fk_app_created` (`created_by_id`), ADD KEY `fk_app_product` (`product_id`);
ALTER TABLE `customer_credit_ratings` ADD PRIMARY KEY (`id`), ADD KEY `fk_rating_customer` (`customer_id`);
ALTER TABLE `customer_related_parties` ADD PRIMARY KEY (`id`), ADD KEY `fk_rel_party_customer` (`customer_id`), ADD KEY `fk_rel_party_related` (`related_customer_id`);
ALTER TABLE `application_collaterals` ADD PRIMARY KEY (`id`), ADD KEY `fk_collateral_app` (`application_id`), ADD KEY `fk_collateral_type` (`collateral_type_id`);
ALTER TABLE `application_documents` ADD PRIMARY KEY (`id`), ADD KEY `fk_doc_app` (`application_id`), ADD KEY `fk_doc_user` (`uploaded_by_id`);
ALTER TABLE `application_repayment_sources` ADD PRIMARY KEY (`id`), ADD KEY `fk_repayment_app` (`application_id`);
ALTER TABLE `application_history` ADD PRIMARY KEY (`id`), ADD KEY `fk_history_app` (`application_id`), ADD KEY `fk_history_user` (`user_id`);

--
-- AUTO_INCREMENT for tables
--
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `customers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;
ALTER TABLE `products` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `collateral_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `credit_applications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
ALTER TABLE `customer_credit_ratings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `customer_related_parties` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `application_collaterals` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `application_documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `application_repayment_sources` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `application_history` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for tables
--
ALTER TABLE `credit_applications`
  ADD CONSTRAINT `fk_app_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_assigned` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_created` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `customer_credit_ratings` ADD CONSTRAINT `fk_rating_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `customer_related_parties`
  ADD CONSTRAINT `fk_rel_party_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rel_party_related` FOREIGN KEY (`related_customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `application_collaterals`
  ADD CONSTRAINT `fk_collateral_app` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_collateral_type` FOREIGN KEY (`collateral_type_id`) REFERENCES `collateral_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `application_documents`
  ADD CONSTRAINT `fk_doc_app` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `application_repayment_sources` ADD CONSTRAINT `fk_repayment_app` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `application_history`
  ADD CONSTRAINT `fk_history_app` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

