-- SQL cấu trúc và dữ liệu mẫu cho Tỉnh/Thành phố, Quận/Huyện, Phường/Xã Việt Nam
-- Chỉ là mẫu, bạn nên import file đầy đủ từ nguồn open source nếu cần toàn bộ dữ liệu

-- Bảng Tỉnh/Thành phố
CREATE TABLE provinces (
  code VARCHAR(20) PRIMARY KEY,
  name NVARCHAR(100) NOT NULL
);

-- Bảng Quận/Huyện
CREATE TABLE districts (
  code VARCHAR(20) PRIMARY KEY,
  name NVARCHAR(100) NOT NULL,
  province_code VARCHAR(20) NOT NULL,
  FOREIGN KEY (province_code) REFERENCES provinces(code)
);

-- Bảng Phường/Xã
CREATE TABLE wards (
  code VARCHAR(20) PRIMARY KEY,
  name NVARCHAR(100) NOT NULL,
  district_code VARCHAR(20) NOT NULL,
  FOREIGN KEY (district_code) REFERENCES districts(code)
);

-- Dữ liệu mẫu (2 tỉnh, 2 quận/huyện mỗi tỉnh, 2 phường/xã mỗi quận)
INSERT INTO provinces (code, name) VALUES
('01', N'Thành phố Hà Nội'),
('79', N'Thành phố Hồ Chí Minh');

INSERT INTO districts (code, name, province_code) VALUES
('001', N'Quận Ba Đình', '01'),
('002', N'Quận Hoàn Kiếm', '01'),
('760', N'Quận 1', '79'),
('761', N'Quận 2', '79');

INSERT INTO wards (code, name, district_code) VALUES
('00001', N'Phường Phúc Xá', '001'),
('00004', N'Phường Trúc Bạch', '001'),
('00006', N'Phường Hàng Buồm', '002'),
('00008', N'Phường Hàng Đào', '002'),
('26734', N'Phường Tân Định', '760'),
('26737', N'Phường Đa Kao', '760'),
('26740', N'Phường Thảo Điền', '761'),
('26743', N'Phường An Phú', '761');

-- Để có đầy đủ dữ liệu, hãy import file SQL từ:
-- https://github.com/kenzouno1/DiaGioiHanhChinhVN hoặc https://github.com/madnh/hanhchinhvn
