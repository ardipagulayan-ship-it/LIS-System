/*
 Navicat Premium Data Transfer

 Source Server         : localhost_3306
 Source Server Type    : MySQL
 Source Server Version : 80403
 Source Host           : localhost:3306
 Source Schema         : lis_db

 Target Server Type    : MySQL
 Target Server Version : 80403
 File Encoding         : 65001

 Date: 23/05/2025 10:22:35
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for analyzers
-- ----------------------------
DROP TABLE IF EXISTS `analyzers`;
CREATE TABLE `analyzers`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `serial_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `test_category_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `analyzers_test_category_id_foreign`(`test_category_id`) USING BTREE,
  INDEX `analyzers_created_by_foreign`(`created_by`) USING BTREE,
  CONSTRAINT `analyzers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `analyzers_test_category_id_foreign` FOREIGN KEY (`test_category_id`) REFERENCES `test_categories` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of analyzers
-- ----------------------------
INSERT INTO `analyzers` VALUES (1, 'Sysmex XN-550', 'Sysmex', 'XN-550', 'SYSXN123456', 'Automated Hematology Analyzer', 4, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (2, 'Mindray BC-6200', 'Mindray', 'BC-6200', 'MINBC123456', 'Automated Hematology Analyzer', 4, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (3, 'Roche Cobas c501', 'Roche', 'Cobas c501', 'ROC501123456', 'Clinical Chemistry Analyzer', 1, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (4, 'Abbott Architect c4000', 'Abbott', 'Architect c4000', 'ABB4000123456', 'Clinical Chemistry Analyzer', 1, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (5, 'BacT/ALERT 3D', 'BioMérieux', 'BacT/ALERT 3D', 'BIO3D123456', 'Microbial Detection System', 2, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (6, 'Abbott ARCHITECT i2000SR', 'Abbott', 'ARCHITECT i2000SR', 'ABBI2000123456', 'Immunoassay Analyzer', NULL, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (7, 'Manual', 'Generic', 'Manual Method', 'MANUAL001', 'Manual testing methods', NULL, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');
INSERT INTO `analyzers` VALUES (8, 'Microscopy', 'Generic', 'Microscope', 'MICRO001', 'Microscopic examination', NULL, 1, 1, '2025-04-29 09:14:14', '2025-04-29 09:14:14');

-- ----------------------------
-- Table structure for analyzers_back
-- ----------------------------
DROP TABLE IF EXISTS `analyzers_back`;
CREATE TABLE `analyzers_back`  (
  `id` bigint(0) NOT NULL,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `brand` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `model` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `serial_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `remarks` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `test_category_id` char(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `isActive` tinyint(1) NULL DEFAULT 0,
  `created_by` char(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `cerated_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of analyzers_back
-- ----------------------------

-- ----------------------------
-- Table structure for assessments
-- ----------------------------
DROP TABLE IF EXISTS `assessments`;
CREATE TABLE `assessments`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `fitness_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `assessments_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `assessments_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of assessments
-- ----------------------------

-- ----------------------------
-- Table structure for birth_histories
-- ----------------------------
DROP TABLE IF EXISTS `birth_histories`;
CREATE TABLE `birth_histories`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `gestation_term` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `past_term` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `post_term` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `age_of_gestation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `type_of_delivery` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `afgar_score` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `weight_of_birth` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `place_of_delivery` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `length_of_stay_at_nursery` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `others` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `immunization_history` json NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `birth_histories_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `birth_histories_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of birth_histories
-- ----------------------------

-- ----------------------------
-- Table structure for consultation_lab_requests
-- ----------------------------
DROP TABLE IF EXISTS `consultation_lab_requests`;
CREATE TABLE `consultation_lab_requests`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `laboratory_request_id` bigint(0) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `consultation_lab_requests_consultation_id_foreign`(`consultation_id`) USING BTREE,
  INDEX `consultation_lab_requests_laboratory_request_id_foreign`(`laboratory_request_id`) USING BTREE,
  CONSTRAINT `consultation_lab_requests_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `consultation_lab_requests_laboratory_request_id_foreign` FOREIGN KEY (`laboratory_request_id`) REFERENCES `laboratory_requests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of consultation_lab_requests
-- ----------------------------

-- ----------------------------
-- Table structure for consultations
-- ----------------------------
DROP TABLE IF EXISTS `consultations`;
CREATE TABLE `consultations`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` bigint(0) UNSIGNED NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `urgency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referring_physician_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `created_by` bigint(0) UNSIGNED NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `consultations_consultation_id_unique`(`consultation_id`) USING BTREE,
  INDEX `consultations_patient_id_foreign`(`patient_id`) USING BTREE,
  INDEX `consultations_referring_physician_id_foreign`(`referring_physician_id`) USING BTREE,
  INDEX `consultations_created_by_foreign`(`created_by`) USING BTREE,
  CONSTRAINT `consultations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `consultations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `consultations_referring_physician_id_foreign` FOREIGN KEY (`referring_physician_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of consultations
-- ----------------------------
INSERT INTO `consultations` VALUES (1, 'CONS-S1PPCK6V', 10, 'High Fever', 'Routine', NULL, 1, 'pending', '2025-05-19 03:09:53', '2025-05-19 03:09:53');
INSERT INTO `consultations` VALUES (2, 'CONS-7OTR5FPA', 13, 'Consultation', 'Routine', NULL, 1, 'pending', '2025-05-19 03:14:34', '2025-05-19 03:14:34');

-- ----------------------------
-- Table structure for diagnoses
-- ----------------------------
DROP TABLE IF EXISTS `diagnoses`;
CREATE TABLE `diagnoses`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `doctor_id` bigint(0) UNSIGNED NOT NULL,
  `diagnosis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinical_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `diagnoses_consultation_id_foreign`(`consultation_id`) USING BTREE,
  INDEX `diagnoses_doctor_id_foreign`(`doctor_id`) USING BTREE,
  CONSTRAINT `diagnoses_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `diagnoses_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of diagnoses
-- ----------------------------

-- ----------------------------
-- Table structure for diagnostics
-- ----------------------------
DROP TABLE IF EXISTS `diagnostics`;
CREATE TABLE `diagnostics`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `chest_xray` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `urinalysis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `cbc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ecg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `stool_exam` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `drug_test` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `hbsag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `chemistry` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `blood_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `others` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `diagnostics_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `diagnostics_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of diagnostics
-- ----------------------------

-- ----------------------------
-- Table structure for doctor_notes
-- ----------------------------
DROP TABLE IF EXISTS `doctor_notes`;
CREATE TABLE `doctor_notes`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `doctor_notes_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `doctor_notes_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of doctor_notes
-- ----------------------------

-- ----------------------------
-- Table structure for doctors
-- ----------------------------
DROP TABLE IF EXISTS `doctors`;
CREATE TABLE `doctors`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of doctors
-- ----------------------------
INSERT INTO `doctors` VALUES (1, 'Dr. Monte Zboncak', 'Neurology', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (2, 'Dr. Miss Simone Bashirian Sr.', 'General Medicine', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (3, 'Dr. Ethyl Gutkowski I', 'Pediatrics', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (4, 'Dr. Charlene Sanford', 'Surgery', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (5, 'Dr. Karley Yundt DDS', 'Neurology', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (6, 'Dr. Tabitha Wehner', 'Cardiology', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (7, 'Dr. Mrs. Cathy Hackett', 'Radiology', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (8, 'Dr. Mr. Crawford Becker', 'General Medicine', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (9, 'Dr. Mrs. Naomie Ankunding I', 'Oncology', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `doctors` VALUES (10, 'Dr. Ebba Rippin DVM', 'General Medicine', '2025-05-15 09:01:53', '2025-05-15 09:01:53');

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `failed_jobs_uuid_unique`(`uuid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for family_histories
-- ----------------------------
DROP TABLE IF EXISTS `family_histories`;
CREATE TABLE `family_histories`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `father` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `mother` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `others` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `specifics` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `family_histories_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `family_histories_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of family_histories
-- ----------------------------

-- ----------------------------
-- Table structure for laboratory_request_test
-- ----------------------------
DROP TABLE IF EXISTS `laboratory_request_test`;
CREATE TABLE `laboratory_request_test`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `laboratory_request_id` bigint(0) UNSIGNED NOT NULL,
  `test_id` bigint(0) UNSIGNED NOT NULL,
  `price_at_request` decimal(8, 2) NULL DEFAULT NULL,
  `item_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `collected_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `collected_at` timestamp(0) NULL DEFAULT NULL,
  `accepted_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `accepted_at` timestamp(0) NULL DEFAULT NULL,
  `rejected_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `rejected_at` timestamp(0) NULL DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `checkin_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `checkin_at` timestamp(0) NULL DEFAULT NULL,
  `sendout_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `sendout_at` timestamp(0) NULL DEFAULT NULL,
  `park_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `park_at` timestamp(0) NULL DEFAULT NULL,
  `perform_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `perform_at` timestamp(0) NULL DEFAULT NULL,
  `verify_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `verify_at` timestamp(0) NULL DEFAULT NULL,
  `pathologist_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `laboratory_request_test_laboratory_request_id_foreign`(`laboratory_request_id`) USING BTREE,
  INDEX `laboratory_request_test_test_id_foreign`(`test_id`) USING BTREE,
  INDEX `laboratory_request_test_collected_by_foreign`(`collected_by`) USING BTREE,
  INDEX `laboratory_request_test_accepted_by_foreign`(`accepted_by`) USING BTREE,
  INDEX `laboratory_request_test_rejected_by_foreign`(`rejected_by`) USING BTREE,
  INDEX `laboratory_request_test_item_status_index`(`item_status`) USING BTREE,
  INDEX `laboratory_request_test_checkin_by_foreign`(`checkin_by`) USING BTREE,
  INDEX `laboratory_request_test_sendout_by_foreign`(`sendout_by`) USING BTREE,
  INDEX `laboratory_request_test_park_by_foreign`(`park_by`) USING BTREE,
  INDEX `laboratory_request_test_perform_by_foreign`(`perform_by`) USING BTREE,
  INDEX `laboratory_request_test_verify_by_foreign`(`verify_by`) USING BTREE,
  INDEX `laboratory_request_test_pathologist_id_foreign`(`pathologist_id`) USING BTREE,
  CONSTRAINT `laboratory_request_test_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_checkin_by_foreign` FOREIGN KEY (`checkin_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_laboratory_request_id_foreign` FOREIGN KEY (`laboratory_request_id`) REFERENCES `laboratory_requests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_park_by_foreign` FOREIGN KEY (`park_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_pathologist_id_foreign` FOREIGN KEY (`pathologist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_perform_by_foreign` FOREIGN KEY (`perform_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_sendout_by_foreign` FOREIGN KEY (`sendout_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_request_test_verify_by_foreign` FOREIGN KEY (`verify_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 603 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of laboratory_request_test
-- ----------------------------
INSERT INTO `laboratory_request_test` VALUES (1, 1, 15, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (2, 1, 11, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (3, 2, 11, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (4, 3, 10, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (5, 4, 4, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (6, 4, 3, 300.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (7, 5, 5, 550.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (8, 5, 13, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (9, 5, 15, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (10, 5, 1, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (11, 5, 11, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (12, 6, 4, 450.00, 'Result Validated', '2025-04-29 06:09:46', '2025-05-07 11:58:50', 1, '2025-04-30 00:04:10', 1, '2025-04-30 00:05:18', NULL, NULL, NULL, 'RESULTS VERIFIED', 'NO COMMENTS', NULL, NULL, NULL, NULL, NULL, NULL, 9, '2025-05-07 11:58:50', 7, '2025-05-07 11:58:50', 2);
INSERT INTO `laboratory_request_test` VALUES (13, 7, 13, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (14, 7, 6, 800.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (15, 8, 10, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (16, 8, 8, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (17, 9, 5, 550.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (18, 9, 13, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (19, 9, 9, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (20, 9, 7, 300.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (21, 9, 15, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (22, 10, 5, 550.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (23, 10, 2, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (24, 11, 2, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (25, 12, 13, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (26, 12, 14, 550.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (27, 12, 8, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (28, 13, 4, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (29, 14, 1, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (30, 14, 2, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (31, 14, 10, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (32, 14, 3, 300.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (33, 14, 15, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (34, 15, 7, 300.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (35, 15, 10, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (36, 15, 11, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (37, 15, 15, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (38, 16, 8, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (39, 16, 10, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (40, 16, 5, 550.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (41, 16, 4, 450.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (42, 17, 9, 250.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (43, 17, 12, 400.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (44, 17, 15, 350.00, 'Pending', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (45, 18, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (46, 18, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (47, 19, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (48, 19, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (49, 19, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (50, 19, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (51, 19, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (52, 20, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (53, 20, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (54, 21, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (55, 21, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (56, 21, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (57, 21, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (58, 21, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (59, 22, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (60, 22, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (61, 22, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (62, 23, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (63, 24, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (64, 24, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (65, 24, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (66, 25, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (67, 25, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (68, 26, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (69, 26, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (70, 26, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (71, 27, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (72, 27, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (73, 27, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (74, 28, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (75, 28, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (76, 29, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (77, 29, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (78, 29, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (79, 29, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (80, 29, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (81, 30, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (82, 30, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (83, 30, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (84, 30, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (85, 31, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (86, 31, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (87, 32, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (88, 32, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (89, 33, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (90, 33, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (91, 33, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (92, 34, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (93, 34, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (94, 34, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (95, 34, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (96, 34, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (97, 35, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (98, 36, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (99, 36, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (100, 36, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (101, 36, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (102, 37, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (103, 38, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (104, 38, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (105, 38, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (106, 38, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (107, 39, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (108, 39, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (109, 39, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (110, 39, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (111, 40, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (112, 40, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (113, 41, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (114, 41, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (115, 41, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (116, 41, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (117, 42, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (118, 43, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (119, 43, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (120, 44, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (121, 44, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (122, 44, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (123, 44, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (124, 45, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (125, 46, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (126, 46, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (127, 46, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (128, 46, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (129, 47, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (130, 47, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (131, 47, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (132, 47, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (133, 47, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (134, 48, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (135, 48, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (136, 49, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (137, 49, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (138, 49, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (139, 49, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (140, 49, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (141, 50, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (142, 51, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (143, 51, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (144, 51, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (145, 51, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (146, 51, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (147, 52, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (148, 52, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (149, 52, 12, 400.00, 'Checked-in', '2025-04-29 06:09:47', '2025-04-30 00:05:27', 1, '2025-04-30 00:03:44', 1, '2025-04-30 00:05:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (150, 52, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (151, 52, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (152, 53, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (153, 53, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (154, 53, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (155, 53, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (156, 54, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (157, 54, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (158, 54, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (159, 55, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (160, 56, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (161, 56, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (162, 56, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (163, 57, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (164, 58, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (165, 58, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (166, 58, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (167, 58, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (168, 59, 7, 300.00, 'Result Printed', '2025-04-29 06:09:47', '2025-05-07 08:58:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (169, 59, 10, 250.00, 'Result Printed', '2025-04-29 06:09:47', '2025-05-07 08:58:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (170, 59, 11, 450.00, 'Result Printed', '2025-04-29 06:09:47', '2025-05-07 08:58:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (171, 59, 4, 450.00, 'Result Printed', '2025-04-29 06:09:47', '2025-05-07 08:58:16', 1, '2025-04-29 06:12:14', 1, '2025-04-29 06:12:24', NULL, NULL, NULL, 'RESULTS VERIFIED', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2025-05-07 08:58:16', 7, '2025-05-07 08:57:33', 2);
INSERT INTO `laboratory_request_test` VALUES (172, 60, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (173, 61, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (174, 62, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (175, 63, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (176, 63, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (177, 63, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (178, 64, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (179, 65, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (180, 65, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (181, 65, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (182, 66, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (183, 66, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (184, 66, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (185, 66, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (186, 66, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (187, 67, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (188, 67, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (189, 67, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (190, 67, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (191, 67, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (192, 68, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (193, 69, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (194, 69, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (195, 69, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (196, 70, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (197, 71, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (198, 71, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (199, 72, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (200, 72, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (201, 72, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (202, 72, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (203, 72, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (204, 73, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (205, 74, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (206, 74, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (207, 74, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (208, 74, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (209, 74, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (210, 75, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (211, 75, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (212, 76, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (213, 76, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (214, 76, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (215, 76, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (216, 77, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (217, 77, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (218, 78, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (219, 78, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (220, 78, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (221, 78, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (222, 79, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (223, 80, 5, 550.00, 'Checked-in', '2025-04-29 06:09:47', '2025-05-07 11:26:47', 1, '2025-05-07 11:26:37', 1, '2025-05-07 11:26:39', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-07 11:26:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (224, 80, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (225, 80, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (226, 81, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (227, 81, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (228, 81, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (229, 82, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (230, 82, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (231, 82, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (232, 82, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (233, 82, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (234, 83, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (235, 83, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (236, 83, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (237, 84, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (238, 85, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (239, 85, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (240, 85, 15, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (241, 85, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (242, 85, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (243, 86, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (244, 86, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (245, 86, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (246, 86, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (247, 86, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (248, 87, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (249, 88, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (250, 88, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (251, 88, 9, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (252, 88, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (253, 89, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (254, 89, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (255, 89, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (256, 89, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (257, 89, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (258, 90, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (259, 90, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (260, 90, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (261, 90, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (262, 91, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (263, 91, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (264, 92, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (265, 92, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (266, 93, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (267, 93, 6, 800.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (268, 93, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (269, 93, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (270, 93, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (271, 94, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (272, 94, 12, 400.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (273, 94, 3, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (274, 95, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (275, 95, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (276, 95, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (277, 96, 1, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (278, 97, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (279, 98, 4, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (280, 99, 14, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (281, 99, 10, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (282, 99, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (283, 99, 8, 350.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (284, 99, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (285, 100, 13, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (286, 100, 7, 300.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (287, 100, 11, 450.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (288, 100, 5, 550.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (289, 100, 2, 250.00, 'Pending', '2025-04-29 06:09:47', '2025-04-29 06:09:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (290, 101, 19, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (291, 101, 27, 300.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (292, 101, 21, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (293, 102, 43, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (294, 102, 2, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (295, 102, 7, 300.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (296, 103, 14, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (297, 103, 24, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (298, 104, 19, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (299, 104, 9, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (300, 104, 31, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (301, 104, 32, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (302, 104, 22, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (303, 105, 44, 400.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (304, 105, 28, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (305, 105, 18, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (306, 105, 34, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (307, 106, 11, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (308, 106, 36, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (309, 106, 30, 600.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (310, 107, 19, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (311, 107, 38, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (312, 107, 18, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (313, 108, 45, 800.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (314, 108, 44, 400.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (315, 108, 24, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (316, 108, 34, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (317, 109, 16, 600.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (318, 109, 19, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (319, 110, 9, 250.00, 'Result Printed', '2025-04-29 06:36:30', '2025-05-07 02:38:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (320, 110, 41, 350.50, 'Result Printed', '2025-04-29 06:36:30', '2025-05-07 02:38:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (321, 110, 28, 550.00, 'Result Printed', '2025-04-29 06:36:30', '2025-05-07 02:38:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (322, 110, 11, 450.00, 'Result Printed', '2025-04-29 06:36:30', '2025-05-07 02:38:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (323, 110, 4, 450.00, 'Result Printed', '2025-04-29 06:36:30', '2025-05-07 02:38:50', 1, '2025-04-30 00:03:52', 1, '2025-04-30 00:05:25', NULL, NULL, NULL, 'RESULTS VERIFIED.\nAll results ran twice', 'No diagnosis found', NULL, NULL, NULL, NULL, NULL, NULL, 9, '2025-05-07 02:38:50', 7, '2025-05-07 02:38:26', 3);
INSERT INTO `laboratory_request_test` VALUES (324, 111, 31, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (325, 111, 22, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (326, 111, 37, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (327, 111, 1, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (328, 111, 11, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (329, 112, 34, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (330, 112, 37, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (331, 112, 3, 300.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (332, 113, 28, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (333, 113, 3, 300.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (334, 113, 32, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (335, 113, 16, 600.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (336, 114, 8, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (337, 114, 44, 400.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (338, 114, 18, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (339, 114, 38, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (340, 115, 9, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (341, 116, 34, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (342, 116, 19, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (343, 116, 23, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (344, 116, 1, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (345, 117, 29, 400.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (346, 118, 18, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (347, 118, 9, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (348, 118, 20, 600.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (349, 119, 31, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (350, 120, 23, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (351, 120, 39, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (352, 120, 26, 400.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (353, 120, 13, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (354, 121, 2, 250.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (355, 121, 20, 600.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (356, 121, 42, 350.50, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (357, 121, 6, 800.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (358, 122, 15, 350.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (359, 123, 21, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (360, 123, 38, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (361, 123, 17, 600.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (362, 123, 5, 550.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (363, 123, 11, 450.00, 'Pending', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (364, 124, 18, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (365, 124, 45, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (366, 124, 5, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (367, 124, 7, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (368, 125, 31, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (369, 125, 36, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (370, 126, 29, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (371, 127, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (372, 128, 34, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (373, 128, 27, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (374, 129, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (375, 129, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (376, 129, 14, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (377, 130, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (378, 131, 19, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (379, 131, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (380, 131, 28, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (381, 131, 43, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (382, 132, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (383, 133, 42, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (384, 134, 23, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (385, 134, 28, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (386, 134, 35, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (387, 134, 13, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (388, 134, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (389, 135, 2, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (390, 136, 34, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (391, 136, 17, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (392, 136, 37, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (393, 136, 28, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (394, 136, 35, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (395, 137, 18, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (396, 137, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (397, 137, 43, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (398, 137, 4, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (399, 138, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (400, 139, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (401, 139, 34, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (402, 139, 5, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (403, 139, 37, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (404, 140, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (405, 141, 45, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (406, 141, 8, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (407, 141, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (408, 141, 39, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (409, 142, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (410, 143, 5, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (411, 143, 42, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (412, 143, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (413, 143, 40, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (414, 144, 17, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (415, 144, 13, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (416, 144, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (417, 144, 38, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (418, 145, 23, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (419, 146, 38, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (420, 146, 10, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (421, 146, 2, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (422, 147, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (423, 147, 7, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (424, 147, 45, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (425, 147, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (426, 147, 5, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (427, 148, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (428, 149, 31, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (429, 149, 12, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (430, 149, 10, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (431, 149, 23, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (432, 149, 26, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (433, 150, 18, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (434, 150, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (435, 150, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (436, 151, 28, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (437, 151, 19, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (438, 152, 13, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (439, 152, 6, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (440, 153, 12, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (441, 154, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (442, 155, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (443, 156, 10, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (444, 156, 3, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (445, 156, 20, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (446, 156, 5, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (447, 156, 26, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (448, 157, 13, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (449, 157, 38, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (450, 157, 15, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (451, 157, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (452, 157, 42, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (453, 158, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (454, 158, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (455, 158, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (456, 159, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (457, 159, 11, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (458, 160, 10, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (459, 160, 29, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (460, 161, 30, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (461, 161, 35, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (462, 161, 45, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (463, 161, 20, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (464, 162, 11, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (465, 162, 29, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (466, 162, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (467, 162, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (468, 163, 45, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (469, 163, 3, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (470, 163, 31, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (471, 163, 17, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (472, 164, 39, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (473, 164, 31, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (474, 164, 36, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (475, 164, 6, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (476, 164, 10, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (477, 165, 29, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (478, 165, 42, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (479, 165, 17, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (480, 166, 38, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (481, 167, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (482, 167, 33, 600.00, 'Checked-in', '2025-04-29 06:36:31', '2025-04-30 00:05:30', 1, '2025-04-30 00:03:37', 1, '2025-04-30 00:05:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (483, 167, 16, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (484, 168, 2, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (485, 168, 29, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (486, 168, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (487, 168, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (488, 169, 23, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (489, 169, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (490, 169, 26, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (491, 170, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (492, 170, 2, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (493, 170, 6, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (494, 170, 20, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (495, 171, 27, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (496, 171, 19, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (497, 171, 21, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (498, 171, 11, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (499, 172, 15, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (500, 172, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (501, 173, 5, 550.00, 'Send-out', '2025-04-29 06:36:31', '2025-05-05 02:30:35', 1, '2025-05-05 02:30:23', 1, '2025-05-05 02:30:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-05-05 02:30:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (502, 173, 34, 550.00, 'Parked', '2025-04-29 06:36:31', '2025-05-05 02:30:46', 1, '2025-05-05 02:30:27', 1, '2025-05-05 02:30:29', NULL, NULL, 'For 24h incubation', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-05-05 02:30:46', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (503, 174, 13, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (504, 175, 12, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (505, 175, 44, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (506, 175, 19, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (507, 175, 4, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (508, 176, 40, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (509, 176, 33, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (510, 176, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (511, 176, 4, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (512, 177, 18, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (513, 178, 38, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (514, 179, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (515, 180, 22, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (516, 180, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (517, 180, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (518, 180, 4, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (519, 181, 33, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (520, 181, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (521, 181, 38, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (522, 181, 28, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (523, 182, 2, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (524, 182, 14, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (525, 183, 22, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (526, 183, 6, 800.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (527, 183, 31, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (528, 184, 7, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (529, 185, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (530, 186, 33, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (531, 186, 36, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (532, 186, 7, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (533, 186, 34, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (534, 186, 31, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (535, 187, 8, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (536, 187, 7, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (537, 187, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (538, 187, 37, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (539, 187, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (540, 188, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (541, 189, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (542, 189, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (543, 189, 5, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (544, 189, 19, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (545, 190, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (546, 190, 28, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (547, 190, 17, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (548, 190, 22, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (549, 190, 19, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (550, 191, 12, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (551, 191, 8, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (552, 192, 13, 450.00, 'Checked-in', '2025-04-29 06:36:31', '2025-05-06 02:06:36', 1, '2025-05-06 02:06:28', 1, '2025-05-06 02:06:30', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-06 02:06:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (553, 192, 14, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (554, 192, 33, 600.00, 'Checked-in', '2025-04-29 06:36:31', '2025-04-30 00:04:30', 1, '2025-04-30 00:03:24', 1, '2025-04-30 00:04:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (555, 192, 36, 250.00, 'Checked-in', '2025-04-29 06:36:31', '2025-05-05 02:29:45', 1, '2025-04-29 10:32:52', 1, '2025-04-30 00:03:27', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-05 02:29:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (556, 193, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (557, 193, 24, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (558, 193, 33, 600.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (559, 194, 3, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (560, 194, 32, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (561, 195, 12, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (562, 196, 2, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (563, 196, 23, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (564, 196, 18, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (565, 196, 9, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (566, 196, 14, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (567, 197, 27, 300.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (568, 197, 1, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (569, 197, 29, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (570, 197, 8, 350.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (571, 198, 12, 400.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (572, 198, 18, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (573, 198, 41, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (574, 198, 42, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (575, 198, 43, 350.50, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (576, 199, 25, 450.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (577, 199, 34, 550.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (578, 200, 36, 250.00, 'Pending', '2025-04-29 06:36:31', '2025-04-29 06:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (579, 201, 12, 400.00, 'Pending', '2025-04-30 08:22:37', '2025-04-30 08:22:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (580, 201, 17, 600.00, 'Pending', '2025-04-30 08:22:37', '2025-04-30 08:22:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (581, 201, 27, 300.00, 'Pending', '2025-04-30 08:22:37', '2025-04-30 08:22:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (582, 201, 40, 350.00, 'Pending', '2025-04-30 08:22:37', '2025-04-30 08:22:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (583, 201, 44, 400.00, 'Pending', '2025-04-30 08:22:37', '2025-04-30 08:22:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (584, 202, 5, 550.00, 'Pending', '2025-04-30 08:27:57', '2025-04-30 08:27:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (585, 203, 17, 600.00, 'Pending', '2025-04-30 08:39:41', '2025-04-30 08:39:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (586, 204, 17, 600.00, 'Checked-in', '2025-04-30 08:48:11', '2025-04-30 08:49:45', 1, '2025-04-30 08:49:35', 1, '2025-04-30 08:49:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (587, 205, 26, 400.00, 'Checked-in', '2025-04-30 09:02:14', '2025-04-30 09:10:28', 1, '2025-04-30 09:10:19', 1, '2025-04-30 09:10:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (588, 206, 4, 450.00, 'Processing Results', '2025-05-06 02:10:16', '2025-05-07 09:43:13', 1, '2025-05-06 02:10:59', 1, '2025-05-06 02:11:01', NULL, NULL, NULL, 'RESULTS VERIFIED', NULL, 1, '2025-05-06 02:11:07', NULL, NULL, NULL, NULL, 11, '2025-05-07 09:43:13', 5, '2025-05-07 09:43:13', 3);
INSERT INTO `laboratory_request_test` VALUES (589, 207, 21, 450.00, 'Processing Results', '2025-05-12 23:10:32', '2025-05-15 07:58:59', 1, '2025-05-12 23:15:27', 1, '2025-05-12 23:15:29', NULL, NULL, NULL, 'RESULTS VERIFIED', NULL, 1, '2025-05-12 23:15:43', NULL, NULL, NULL, NULL, 11, '2025-05-15 07:58:59', 12, '2025-05-15 07:58:59', 3);
INSERT INTO `laboratory_request_test` VALUES (590, 208, 4, 450.00, 'Processing Results', '2025-05-13 03:00:38', '2025-05-13 03:21:01', 1, '2025-05-13 03:01:43', 1, '2025-05-13 03:01:48', NULL, NULL, NULL, 'RESULTS VERIFIED', NULL, 1, '2025-05-13 03:02:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (591, 208, 21, 450.00, 'Processing Results', '2025-05-13 03:00:38', '2025-05-13 23:47:26', 1, '2025-05-13 03:01:46', 1, '2025-05-13 03:01:50', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 03:02:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (592, 209, 46, 500.00, 'Processing Results', '2025-05-13 06:51:11', '2025-05-13 06:51:51', 1, '2025-05-13 06:51:40', 1, '2025-05-13 06:51:42', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 06:51:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (593, 209, 47, 300.00, 'Processing Results', '2025-05-13 06:51:11', '2025-05-14 00:08:13', 1, '2025-05-13 06:51:40', 1, '2025-05-13 06:51:42', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 06:51:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (594, 209, 48, 1500.00, 'Processing Results', '2025-05-13 06:51:11', '2025-05-14 00:08:13', 1, '2025-05-13 06:51:40', 1, '2025-05-13 06:51:42', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 06:51:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (595, 209, 49, 300.00, 'Processing Results', '2025-05-13 06:51:11', '2025-05-14 00:08:13', 1, '2025-05-13 06:51:40', 1, '2025-05-13 06:51:42', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 06:51:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (596, 210, 4, 450.00, 'Checked-in', '2025-05-13 23:50:24', '2025-05-13 23:51:16', 1, '2025-05-13 23:50:50', 1, '2025-05-13 23:50:52', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 23:51:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (597, 210, 47, 300.00, 'Processing Results', '2025-05-13 23:50:24', '2025-05-14 00:17:34', 1, '2025-05-13 23:50:54', 1, '2025-05-13 23:50:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (598, 210, 48, 1500.00, 'Processing Results', '2025-05-13 23:50:24', '2025-05-14 00:17:34', 1, '2025-05-13 23:50:54', 1, '2025-05-13 23:50:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 23:51:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (599, 210, 49, 300.00, 'Processing Results', '2025-05-13 23:50:24', '2025-05-13 23:51:22', 1, '2025-05-13 23:50:54', 1, '2025-05-13 23:50:56', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-13 23:51:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (600, 211, 21, 450.00, 'Processing Results', '2025-05-15 00:28:26', '2025-05-15 00:37:11', 1, '2025-05-15 00:36:07', 1, '2025-05-15 00:36:09', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-15 00:37:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (601, 212, 4, 450.00, 'Result Printed', '2025-05-15 00:44:03', '2025-05-15 00:44:41', 1, '2025-05-15 00:44:33', 1, '2025-05-15 00:44:35', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-15 00:44:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `laboratory_request_test` VALUES (602, 212, 21, 450.00, 'Checked-in', '2025-05-15 00:44:03', '2025-05-15 00:44:44', 1, '2025-05-15 00:44:34', 1, '2025-05-15 00:44:35', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-15 00:44:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for laboratory_requests
-- ----------------------------
DROP TABLE IF EXISTS `laboratory_requests`;
CREATE TABLE `laboratory_requests`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(0) UNSIGNED NOT NULL,
  `doctor_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `sample_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ward_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `room_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `referring_doctor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `priority_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `patient_type_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `created_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `request_date` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `laboratory_requests_patient_id_foreign`(`patient_id`) USING BTREE,
  INDEX `laboratory_requests_doctor_id_foreign`(`doctor_id`) USING BTREE,
  INDEX `laboratory_requests_ward_id_foreign`(`ward_id`) USING BTREE,
  INDEX `laboratory_requests_room_id_foreign`(`room_id`) USING BTREE,
  INDEX `laboratory_requests_patient_type_id_foreign`(`patient_type_id`) USING BTREE,
  INDEX `laboratory_requests_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `laboratory_requests_priority_level_index`(`priority_level`) USING BTREE,
  CONSTRAINT `laboratory_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_requests_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_requests_patient_type_id_foreign` FOREIGN KEY (`patient_type_id`) REFERENCES `patient_types` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_requests_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `laboratory_requests_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 213 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of laboratory_requests
-- ----------------------------
INSERT INTO `laboratory_requests` VALUES (1, 41, 1, '3', 2, 4, NULL, 'Urgent', 4, 1, '2025-04-11 22:24:25', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (2, 34, 3, '1', 1, 2, NULL, 'STAT', 4, 1, '2025-04-23 18:03:41', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (3, 38, 8, '2', 3, 7, NULL, 'Routine', 3, 1, '2025-04-10 10:33:10', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (4, 27, 3, '5', 2, 4, NULL, 'STAT', 3, 1, '2025-04-01 21:53:22', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (5, 2, 8, '4', 2, 4, '1', 'Routine', 1, 1, '2025-03-31 22:05:22', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (6, 21, 3, '5', 4, 8, NULL, 'Urgent', 1, 1, '2025-04-06 05:13:49', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (7, 26, 5, '1', 1, 1, '1', 'STAT', 4, 1, '2025-04-09 17:44:20', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (8, 50, 5, '5', 4, 9, '1', 'Routine', 4, 1, '2025-04-23 15:47:06', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (9, 24, 8, '5', 4, 8, NULL, 'Urgent', 2, 1, '2025-04-04 13:37:27', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (10, 9, 6, '6', 1, 1, NULL, 'STAT', 2, 1, '2025-04-07 20:17:57', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (11, 21, 7, '4', 1, 3, NULL, 'STAT', 3, 1, '2025-03-31 21:07:46', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (12, 24, 2, '6', 2, 4, NULL, 'Urgent', 2, 1, '2025-04-14 03:57:19', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (13, 36, 9, '4', 4, 8, NULL, 'Urgent', 3, 1, '2025-04-28 02:26:38', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (14, 42, 9, '2', 1, 3, '1', 'Urgent', 2, 1, '2025-04-04 23:44:33', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (15, 19, 6, '6', 5, 11, '1', 'Urgent', 2, 1, '2025-04-26 15:17:44', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (16, 45, 5, '3', 4, 9, NULL, 'Routine', 1, 1, '2025-04-13 21:36:14', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (17, 50, 1, '6', 1, 2, '1', 'Routine', 4, 1, '2025-04-24 04:23:03', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (18, 13, 8, '2', 3, 7, NULL, 'STAT', 1, 1, '2025-04-20 21:10:29', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (19, 9, 5, '3', 2, 4, NULL, 'Routine', 4, 1, '2025-04-03 21:18:50', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (20, 45, 9, '5', 2, 5, '1', 'Routine', 3, 1, '2025-04-06 04:57:41', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (21, 11, 5, '2', 1, 1, NULL, 'Urgent', 2, 1, '2025-04-22 05:31:02', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (22, 2, 9, '1', 4, 8, NULL, 'Routine', 2, 1, '2025-04-21 23:51:48', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (23, 13, 10, '2', 3, 6, NULL, 'Urgent', 4, 1, '2025-04-12 01:36:28', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (24, 47, 2, '2', 5, 11, NULL, 'STAT', 4, 1, '2025-04-27 19:06:19', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (25, 5, 9, '5', 4, 8, '1', 'Routine', 1, 1, '2025-04-26 09:31:28', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (26, 39, 9, '3', 3, 6, NULL, 'Routine', 2, 1, '2025-03-31 07:01:53', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (27, 17, 10, '3', 4, 8, '1', 'Urgent', 1, 1, '2025-04-18 22:50:33', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (28, 29, 2, '3', 2, 4, '1', 'STAT', 3, 1, '2025-04-23 00:04:17', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (29, 27, 6, '3', 3, 7, NULL, 'STAT', 4, 1, '2025-04-28 10:18:07', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (30, 7, 2, '1', 3, 7, '1', 'STAT', 2, 1, '2025-04-11 18:51:22', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (31, 14, 1, '4', 1, 2, NULL, 'Routine', 3, 1, '2025-04-17 18:09:32', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (32, 16, 7, '1', 3, 7, '1', 'Routine', 4, 1, '2025-04-04 10:51:24', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (33, 2, 4, '2', 2, 5, NULL, 'Urgent', 3, 1, '2025-04-14 13:36:08', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (34, 7, 6, '4', 1, 3, NULL, 'Routine', 1, 1, '2025-04-14 19:03:24', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (35, 14, 5, '5', 2, 4, NULL, 'Urgent', 1, 1, '2025-04-22 22:10:15', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (36, 23, 8, '3', 3, 7, NULL, 'Urgent', 4, 1, '2025-04-12 14:46:24', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (37, 21, 1, '3', 2, 5, NULL, 'STAT', 2, 1, '2025-04-29 01:54:41', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (38, 41, 4, '6', 4, 8, '1', 'Routine', 1, 1, '2025-04-14 11:38:53', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (39, 18, 8, '3', 2, 4, '1', 'STAT', 2, 1, '2025-04-20 12:16:21', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (40, 21, 7, '5', 1, 3, NULL, 'Routine', 3, 1, '2025-04-15 06:26:51', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (41, 31, 1, '1', 3, 6, '1', 'Urgent', 1, 1, '2025-04-16 14:12:56', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (42, 17, 3, '5', 3, 6, NULL, 'Urgent', 2, 1, '2025-04-18 08:57:39', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (43, 47, 5, '4', 2, 5, NULL, 'Urgent', 1, 1, '2025-04-20 22:27:17', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (44, 27, 7, '1', 1, 1, NULL, 'STAT', 3, 1, '2025-04-20 18:28:29', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (45, 42, 7, '1', 2, 4, '1', 'Urgent', 4, 1, '2025-04-22 10:19:23', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (46, 34, 9, '1', 4, 9, NULL, 'Urgent', 2, 1, '2025-04-29 03:21:24', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (47, 28, 3, '4', 3, 6, '1', 'Urgent', 1, 1, '2025-04-28 06:00:36', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (48, 26, 2, '5', 5, 10, '1', 'STAT', 1, 1, '2025-04-09 10:57:47', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (49, 42, 10, '1', 4, 9, NULL, 'Routine', 4, 1, '2025-04-04 02:29:13', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (50, 23, 3, '3', 3, 6, NULL, 'Routine', 2, 1, '2025-04-28 06:04:10', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (51, 47, 3, '3', 3, 6, NULL, 'Routine', 4, 1, '2025-04-08 21:30:35', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (52, 31, 1, '6', 3, 7, '1', 'Urgent', 4, 1, '2025-04-01 23:14:08', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (53, 5, 8, '4', 1, 3, '1', 'Routine', 3, 1, '2025-04-24 09:36:17', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (54, 33, 3, '2', 5, 11, '1', 'Routine', 4, 1, '2025-04-02 12:15:40', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (55, 31, 10, '3', 2, 4, NULL, 'Urgent', 2, 1, '2025-03-30 08:40:13', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (56, 45, 3, '2', 3, 6, '1', 'Urgent', 2, 1, '2025-04-21 23:28:47', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (57, 26, 5, '6', 5, 10, NULL, 'Routine', 1, 1, '2025-04-02 13:34:50', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (58, 24, 3, '3', 5, 11, NULL, 'Routine', 3, 1, '2025-04-08 19:07:35', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (59, 47, 6, '5', 1, 1, '1', 'Urgent', 4, 1, '2025-04-05 17:35:06', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (60, 12, 10, '3', 2, 4, NULL, 'Urgent', 1, 1, '2025-03-29 14:16:54', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (61, 27, 9, '5', 1, 1, NULL, 'Urgent', 3, 1, '2025-04-16 10:56:03', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (62, 18, 9, '6', 3, 7, NULL, 'STAT', 4, 1, '2025-03-30 15:07:56', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (63, 49, 2, '2', 3, 7, NULL, 'Routine', 1, 1, '2025-04-25 19:30:10', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (64, 8, 4, '2', 4, 8, NULL, 'STAT', 4, 1, '2025-04-11 09:09:08', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (65, 48, 1, '1', 2, 5, NULL, 'Routine', 3, 1, '2025-04-26 09:47:57', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (66, 5, 2, '5', 5, 10, '1', 'Urgent', 1, 1, '2025-04-08 23:39:03', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (67, 31, 5, '6', 2, 5, NULL, 'Urgent', 1, 1, '2025-04-16 13:26:10', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (68, 7, 1, '4', 5, 10, '1', 'Routine', 4, 1, '2025-04-29 02:48:51', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (69, 24, 9, '6', 3, 6, NULL, 'Urgent', 3, 1, '2025-04-13 01:07:45', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (70, 1, 10, '5', 1, 2, NULL, 'STAT', 4, 1, '2025-03-30 06:32:07', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (71, 3, 4, '5', 1, 2, NULL, 'Routine', 2, 1, '2025-04-27 10:23:07', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (72, 39, 3, '2', 2, 5, NULL, 'STAT', 4, 1, '2025-04-12 05:09:56', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (73, 6, 2, '4', 2, 4, NULL, 'Urgent', 1, 1, '2025-04-24 05:53:17', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (74, 41, 6, '3', 3, 6, NULL, 'Urgent', 1, 1, '2025-04-06 02:20:43', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (75, 35, 8, '1', 2, 5, NULL, 'Routine', 4, 1, '2025-04-21 22:22:46', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (76, 50, 4, '6', 4, 8, NULL, 'STAT', 4, 1, '2025-03-31 08:32:57', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (77, 24, 2, '4', 2, 4, NULL, 'Routine', 4, 1, '2025-04-16 04:51:37', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (78, 32, 3, '2', 1, 3, '1', 'Urgent', 2, 1, '2025-04-16 17:12:20', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (79, 41, 4, '1', 4, 9, '1', 'STAT', 3, 1, '2025-04-19 20:06:04', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (80, 44, 3, '2', 5, 10, NULL, 'Urgent', 1, 1, '2025-03-29 22:55:43', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (81, 16, 10, '1', 2, 4, '1', 'Routine', 1, 1, '2025-04-11 05:46:32', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (82, 8, 9, '3', 4, 9, NULL, 'Routine', 3, 1, '2025-04-11 07:42:38', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (83, 20, 10, '5', 2, 4, NULL, 'STAT', 1, 1, '2025-04-20 14:40:43', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (84, 28, 10, '3', 4, 8, NULL, 'STAT', 3, 1, '2025-04-24 08:56:12', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (85, 50, 3, '1', 3, 7, NULL, 'STAT', 4, 1, '2025-03-31 12:55:49', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (86, 41, 1, '4', 1, 2, NULL, 'Urgent', 3, 1, '2025-04-16 12:20:48', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (87, 46, 8, '2', 1, 1, '1', 'STAT', 3, 1, '2025-04-04 08:36:41', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (88, 10, 3, '1', 5, 10, '1', 'STAT', 4, 1, '2025-04-22 21:16:21', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (89, 32, 7, '2', 5, 11, '1', 'Urgent', 1, 1, '2025-04-16 01:26:10', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (90, 37, 9, '1', 1, 3, NULL, 'Urgent', 4, 1, '2025-04-25 03:37:01', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (91, 34, 1, '5', 5, 10, NULL, 'Urgent', 1, 1, '2025-04-26 19:21:37', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (92, 33, 9, '1', 1, 2, NULL, 'Urgent', 3, 1, '2025-03-31 07:41:52', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (93, 40, 7, '4', 3, 6, '1', 'Urgent', 2, 1, '2025-04-25 20:19:11', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (94, 38, 9, '2', 1, 2, '1', 'Routine', 4, 1, '2025-04-11 19:35:32', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (95, 46, 3, '4', 2, 5, NULL, 'Urgent', 3, 1, '2025-04-24 12:20:09', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (96, 2, 8, '4', 1, 3, '1', 'Routine', 2, 1, '2025-04-13 18:18:45', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (97, 6, 3, '6', 2, 4, NULL, 'Routine', 4, 1, '2025-04-05 01:16:28', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (98, 36, 4, '2', 4, 8, NULL, 'Routine', 4, 1, '2025-04-14 23:12:30', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (99, 49, 6, '4', 5, 10, NULL, 'STAT', 4, 1, '2025-04-08 00:04:02', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (100, 36, 4, '6', 5, 11, NULL, 'STAT', 1, 1, '2025-04-01 15:08:28', '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `laboratory_requests` VALUES (101, 120, 6, '1', 3, 7, NULL, 'STAT', 2, 1, '2025-04-27 15:41:14', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (102, 125, 3, '4', 5, 10, NULL, 'Urgent', 3, 1, '2025-04-14 19:01:37', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (103, 65, 7, '6', 1, 1, NULL, 'STAT', 2, 1, '2025-04-26 23:20:32', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (104, 140, 9, '1', 5, 11, '1', 'Routine', 3, 1, '2025-03-30 14:43:33', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (105, 1, 6, '1', 2, 5, '1', 'Urgent', 4, 1, '2025-04-21 07:49:25', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (106, 36, 8, '2', 2, 5, NULL, 'STAT', 1, 1, '2025-04-22 22:28:11', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (107, 143, 6, '1', 4, 9, NULL, 'Urgent', 1, 1, '2025-04-09 11:50:23', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (108, 49, 2, '3', 3, 6, NULL, 'Routine', 4, 1, '2025-04-15 11:36:58', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (109, 115, 3, '4', 3, 7, NULL, 'Urgent', 4, 1, '2025-04-03 14:57:46', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (110, 15, 6, '5', 1, 3, '1', 'Urgent', 1, 1, '2025-04-02 04:55:40', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (111, 76, 10, '6', 4, 9, '1', 'STAT', 3, 1, '2025-04-20 19:51:30', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (112, 49, 3, '1', 5, 11, NULL, 'Urgent', 3, 1, '2025-04-24 17:46:53', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (113, 52, 10, '6', 5, 11, NULL, 'Urgent', 2, 1, '2025-04-27 12:59:14', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (114, 90, 1, '2', 3, 7, NULL, 'Urgent', 4, 1, '2025-04-20 10:48:28', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (115, 105, 5, '4', 1, 3, NULL, 'Routine', 4, 1, '2025-04-20 05:40:16', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (116, 3, 5, '6', 3, 7, NULL, 'STAT', 4, 1, '2025-04-01 20:43:58', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (117, 33, 6, '4', 5, 10, '1', 'Routine', 2, 1, '2025-04-25 15:45:39', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (118, 78, 3, '4', 1, 3, '1', 'Urgent', 4, 1, '2025-04-04 21:58:12', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (119, 123, 8, '3', 2, 4, NULL, 'Routine', 3, 1, '2025-04-25 18:54:59', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (120, 20, 7, '3', 3, 7, '1', 'Routine', 3, 1, '2025-04-13 16:24:22', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (121, 108, 9, '3', 2, 4, NULL, 'STAT', 4, 1, '2025-04-19 08:04:16', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (122, 35, 3, '2', 1, 1, NULL, 'Urgent', 2, 1, '2025-04-08 08:27:53', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (123, 46, 3, '4', 1, 1, '1', 'STAT', 2, 1, '2025-04-03 02:24:00', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (124, 13, 8, '5', 5, 11, NULL, 'STAT', 4, 1, '2025-04-05 04:50:46', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (125, 3, 4, '2', 2, 4, NULL, 'STAT', 1, 1, '2025-04-07 21:59:53', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (126, 8, 7, '2', 2, 4, NULL, 'STAT', 1, 1, '2025-04-06 04:22:29', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (127, 73, 3, '5', 1, 2, NULL, 'Urgent', 3, 1, '2025-04-13 03:25:24', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (128, 95, 5, '5', 2, 5, '1', 'STAT', 3, 1, '2025-04-22 04:35:07', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (129, 146, 2, '3', 2, 5, '1', 'STAT', 1, 1, '2025-04-29 03:59:05', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (130, 87, 8, '3', 4, 9, NULL, 'STAT', 4, 1, '2025-04-06 15:26:20', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (131, 129, 1, '6', 1, 2, '1', 'Routine', 1, 1, '2025-04-09 07:47:38', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (132, 78, 8, '3', 2, 4, NULL, 'Urgent', 2, 1, '2025-04-02 20:06:56', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (133, 137, 9, '4', 4, 9, '1', 'Urgent', 4, 1, '2025-04-27 17:59:24', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (134, 60, 7, '1', 5, 11, NULL, 'Routine', 4, 1, '2025-04-08 15:23:44', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (135, 141, 9, '6', 1, 1, NULL, 'Urgent', 4, 1, '2025-04-29 04:33:15', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (136, 100, 4, '3', 3, 6, NULL, 'Routine', 1, 1, '2025-03-30 14:51:34', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (137, 125, 7, '4', 3, 6, NULL, 'Routine', 3, 1, '2025-04-06 03:00:06', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (138, 137, 9, '1', 5, 11, NULL, 'Routine', 3, 1, '2025-04-12 00:02:57', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (139, 143, 5, '3', 1, 3, '1', 'Routine', 1, 1, '2025-04-15 18:53:50', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (140, 66, 9, '3', 5, 10, NULL, 'Urgent', 3, 1, '2025-04-07 00:30:03', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (141, 11, 9, '6', 5, 10, NULL, 'Urgent', 1, 1, '2025-04-20 01:26:27', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (142, 143, 9, '1', 4, 9, NULL, 'Routine', 4, 1, '2025-04-12 19:17:20', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (143, 57, 2, '3', 2, 5, NULL, 'STAT', 1, 1, '2025-04-15 15:47:39', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (144, 10, 6, '1', 3, 6, NULL, 'Routine', 1, 1, '2025-04-20 05:27:17', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (145, 38, 4, '2', 1, 1, '1', 'STAT', 3, 1, '2025-03-30 23:21:02', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (146, 133, 6, '2', 5, 11, NULL, 'Routine', 3, 1, '2025-04-26 09:36:30', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (147, 4, 5, '4', 5, 10, '1', 'STAT', 4, 1, '2025-04-14 19:20:22', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (148, 112, 7, '5', 5, 11, NULL, 'Routine', 2, 1, '2025-04-17 19:21:07', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (149, 60, 8, '4', 5, 11, NULL, 'Routine', 2, 1, '2025-04-28 00:08:15', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (150, 74, 7, '6', 5, 11, NULL, 'Urgent', 4, 1, '2025-04-20 08:14:29', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (151, 15, 1, '1', 3, 6, '1', 'STAT', 2, 1, '2025-04-24 18:49:37', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (152, 144, 6, '2', 3, 6, NULL, 'Urgent', 4, 1, '2025-04-28 00:56:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (153, 1, 3, '4', 1, 1, NULL, 'Routine', 2, 1, '2025-04-21 02:01:41', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (154, 141, 5, '4', 3, 7, NULL, 'Urgent', 3, 1, '2025-03-31 02:15:37', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (155, 14, 2, '5', 4, 8, NULL, 'Routine', 3, 1, '2025-04-01 16:35:25', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (156, 123, 6, '4', 5, 11, NULL, 'Routine', 3, 1, '2025-03-29 08:26:05', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (157, 1, 1, '2', 5, 11, '1', 'STAT', 3, 1, '2025-04-28 01:09:20', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (158, 25, 2, '5', 5, 11, NULL, 'STAT', 2, 1, '2025-04-11 09:58:58', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (159, 126, 9, '5', 2, 5, NULL, 'STAT', 4, 1, '2025-04-24 03:06:19', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (160, 9, 10, '2', 5, 11, '1', 'STAT', 2, 1, '2025-04-11 11:58:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (161, 48, 9, '4', 3, 7, NULL, 'STAT', 2, 1, '2025-03-31 02:07:12', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (162, 79, 1, '1', 1, 1, '1', 'Urgent', 3, 1, '2025-04-04 20:36:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (163, 31, 7, '2', 3, 7, NULL, 'STAT', 3, 1, '2025-04-13 07:12:07', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (164, 59, 10, '6', 1, 3, NULL, 'Urgent', 1, 1, '2025-04-15 19:59:48', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (165, 18, 3, '1', 1, 1, NULL, 'Routine', 1, 1, '2025-04-05 15:11:43', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (166, 75, 6, '3', 4, 8, '1', 'STAT', 4, 1, '2025-04-09 09:44:08', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (167, 91, 8, '2', 3, 6, '1', 'Urgent', 4, 1, '2025-04-01 05:36:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (168, 26, 2, '1', 4, 9, '1', 'STAT', 2, 1, '2025-04-20 23:09:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (169, 41, 2, '5', 4, 8, NULL, 'STAT', 1, 1, '2025-04-07 16:51:30', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (170, 40, 2, '1', 5, 10, '1', 'STAT', 3, 1, '2025-04-17 07:07:54', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (171, 150, 8, '3', 5, 10, NULL, 'STAT', 1, 1, '2025-04-22 07:10:50', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (172, 24, 8, '6', 4, 8, NULL, 'Urgent', 3, 1, '2025-04-24 00:13:45', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (173, 103, 4, '4', 5, 11, NULL, 'Urgent', 3, 1, '2025-03-29 07:05:28', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (174, 87, 9, '1', 1, 3, NULL, 'Urgent', 2, 1, '2025-04-13 13:59:39', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (175, 87, 8, '2', 5, 10, NULL, 'Routine', 3, 1, '2025-04-25 10:40:48', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (176, 131, 7, '1', 5, 10, NULL, 'Urgent', 3, 1, '2025-04-09 19:45:38', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (177, 126, 4, '3', 4, 8, NULL, 'STAT', 2, 1, '2025-04-02 02:27:47', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (178, 134, 4, '2', 1, 1, '1', 'Urgent', 2, 1, '2025-04-22 23:17:51', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (179, 10, 1, '4', 5, 11, NULL, 'STAT', 3, 1, '2025-04-12 08:27:44', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (180, 32, 7, '2', 1, 1, '1', 'STAT', 2, 1, '2025-04-12 00:13:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (181, 108, 6, '4', 5, 10, NULL, 'STAT', 3, 1, '2025-04-04 05:37:57', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (182, 25, 4, '5', 1, 2, NULL, 'STAT', 2, 1, '2025-03-29 18:56:24', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (183, 101, 10, '6', 3, 6, '1', 'Routine', 4, 1, '2025-04-03 01:06:31', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (184, 121, 9, '2', 2, 4, '1', 'Routine', 1, 1, '2025-04-09 21:35:29', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (185, 19, 8, '1', 5, 11, NULL, 'Routine', 3, 1, '2025-04-18 13:27:42', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (186, 37, 7, '5', 5, 11, NULL, 'Urgent', 1, 1, '2025-04-15 03:07:08', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (187, 53, 2, '2', 4, 8, NULL, 'Urgent', 1, 1, '2025-04-19 13:02:20', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (188, 44, 8, '4', 2, 5, '1', 'Routine', 4, 1, '2025-04-10 01:41:21', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (189, 92, 5, '6', 4, 8, '1', 'Routine', 3, 1, '2025-03-31 05:06:02', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (190, 93, 10, '2', 1, 1, '1', 'Routine', 1, 1, '2025-04-27 07:03:25', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (191, 75, 8, '6', 4, 8, NULL, 'Routine', 3, 1, '2025-04-08 21:42:46', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (192, 68, 10, '6', 4, 8, '1', 'Urgent', 4, 1, '2025-03-30 02:27:10', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (193, 2, 3, '3', 3, 7, '1', 'STAT', 4, 1, '2025-04-05 23:27:57', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (194, 149, 1, '2', 1, 3, '1', 'Urgent', 2, 1, '2025-03-30 16:20:48', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (195, 131, 4, '5', 5, 11, '1', 'STAT', 1, 1, '2025-04-09 09:10:55', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (196, 51, 3, '4', 2, 5, '1', 'STAT', 2, 1, '2025-04-07 06:44:40', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (197, 87, 7, '5', 4, 8, NULL, 'STAT', 3, 1, '2025-04-10 16:59:30', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (198, 95, 9, '1', 5, 11, NULL, 'Routine', 2, 1, '2025-04-05 10:19:19', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (199, 61, 4, '2', 2, 4, NULL, 'Routine', 2, 1, '2025-04-22 17:07:55', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (200, 48, 8, '1', 5, 11, NULL, 'STAT', 3, 1, '2025-04-06 20:04:35', '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `laboratory_requests` VALUES (201, 2, NULL, NULL, 3, 7, NULL, 'Routine', 4, 1, '2025-04-30 08:22:37', '2025-04-30 08:22:37', '2025-04-30 08:22:37');
INSERT INTO `laboratory_requests` VALUES (202, 7, NULL, '122333', 4, 9, NULL, 'Routine', 4, 1, '2025-04-30 08:27:57', '2025-04-30 08:27:57', '2025-04-30 08:27:57');
INSERT INTO `laboratory_requests` VALUES (203, 10, NULL, NULL, 1, 2, NULL, 'Routine', 2, 1, '2025-04-30 08:39:41', '2025-04-30 08:39:41', '2025-04-30 08:39:41');
INSERT INTO `laboratory_requests` VALUES (204, 5, 6, NULL, 4, 9, '2', 'Urgent', 4, 1, '2025-04-30 08:48:11', '2025-04-30 08:48:11', '2025-04-30 08:48:11');
INSERT INTO `laboratory_requests` VALUES (205, 14, 3, '2233344', 4, 9, 'Dr. Prof. Tristin O\'Reilly Jr.', 'Routine', 1, 1, '2025-04-30 09:02:14', '2025-04-30 09:02:14', '2025-04-30 09:02:14');
INSERT INTO `laboratory_requests` VALUES (206, 109, 7, '000045', 1, 1, 'Dr. Ms. Alayna Homenick III', 'Routine', 2, 1, '2025-05-06 02:10:16', '2025-05-06 02:10:16', '2025-05-06 02:10:16');
INSERT INTO `laboratory_requests` VALUES (207, 143, 4, '00000051', 1, 1, 'Dr. Jovanny Koss', 'Urgent', 3, 1, '2025-05-12 23:10:32', '2025-05-12 23:10:32', '2025-05-12 23:10:32');
INSERT INTO `laboratory_requests` VALUES (208, 45, 3, '0000052', 5, 10, 'Dr. Prof. Tristin O\'Reilly Jr.', 'Urgent', 2, 1, '2025-05-13 03:00:38', '2025-05-13 03:00:38', '2025-05-13 03:00:38');
INSERT INTO `laboratory_requests` VALUES (209, 5, NULL, '0000053', NULL, NULL, NULL, 'Routine', NULL, 1, '2025-05-13 06:51:11', '2025-05-13 06:51:11', '2025-05-13 06:51:11');
INSERT INTO `laboratory_requests` VALUES (210, 59, 6, '0000056', 5, 10, 'Dr. Rowland Ward I', 'Urgent', 2, 1, '2025-05-13 23:50:24', '2025-05-13 23:50:24', '2025-05-13 23:50:24');
INSERT INTO `laboratory_requests` VALUES (211, 118, 7, '000057', 3, 6, 'Dr. Elenora Beahan', 'Routine', 2, 1, '2025-05-15 00:28:26', '2025-05-15 00:28:26', '2025-05-15 00:28:26');
INSERT INTO `laboratory_requests` VALUES (212, 7, 1, '000058', 4, 9, 'Dr. Jovanny Koss', 'Urgent', 1, 1, '2025-05-15 00:44:03', '2025-05-15 00:44:03', '2025-05-15 00:44:03');

-- ----------------------------
-- Table structure for maternal_histories
-- ----------------------------
DROP TABLE IF EXISTS `maternal_histories`;
CREATE TABLE `maternal_histories`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `menarche` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `interval` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `duration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_menstrual_period` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `previous_menstrual_period` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `age_of_gestation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `estimated_date_of_confinement` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `gravida` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `parity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `term` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `preterm` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `aborted` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `living` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `others` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `maternal_histories_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `maternal_histories_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of maternal_histories
-- ----------------------------

-- ----------------------------
-- Table structure for medical_certificates
-- ----------------------------
DROP TABLE IF EXISTS `medical_certificates`;
CREATE TABLE `medical_certificates`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `certificate_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `diagnosis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `reason_of_consultation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `examined_on` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `recovery_duration` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `physically_fit_for` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `physically_fit_for_others` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `medical_certificates_consultation_id_foreign`(`consultation_id`) USING BTREE,
  CONSTRAINT `medical_certificates_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of medical_certificates
-- ----------------------------

-- ----------------------------
-- Table structure for medical_histories
-- ----------------------------
DROP TABLE IF EXISTS `medical_histories`;
CREATE TABLE `medical_histories`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `nurse_id` bigint(0) UNSIGNED NOT NULL,
  `entry` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `chief_complaint` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `history_of_present_illness` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `past_medical_history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `other_medical_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `allergies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `adverse_drug_reaction` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `blood_transfusion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `radiation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `previous_hospitalization` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `previous_operations` tinyint(1) NULL DEFAULT NULL,
  `previous_operations_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `current_medications` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `drugs` tinyint(1) NULL DEFAULT NULL,
  `alcohol` tinyint(1) NULL DEFAULT NULL,
  `smoking` tinyint(1) NULL DEFAULT NULL,
  `social_others` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `medical_histories_consultation_id_foreign`(`consultation_id`) USING BTREE,
  INDEX `medical_histories_nurse_id_foreign`(`nurse_id`) USING BTREE,
  CONSTRAINT `medical_histories_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `medical_histories_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of medical_histories
-- ----------------------------
INSERT INTO `medical_histories` VALUES (1, 1, 1, 'None', '2025-05-19 03:44:30', '2025-05-19 03:44:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 45 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '2014_10_12_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES (2, '2014_10_12_100000_create_password_reset_tokens_table', 1);
INSERT INTO `migrations` VALUES (3, '2019_08_19_000000_create_failed_jobs_table', 1);
INSERT INTO `migrations` VALUES (4, '2019_12_14_000001_create_personal_access_tokens_table', 1);
INSERT INTO `migrations` VALUES (5, '2025_03_29_063351_create_patients_table', 1);
INSERT INTO `migrations` VALUES (6, '2025_03_29_063356_create_doctors_table', 1);
INSERT INTO `migrations` VALUES (7, '2025_03_29_063401_create_test_categories_table', 1);
INSERT INTO `migrations` VALUES (8, '2025_03_29_063409_create_tests_table', 1);
INSERT INTO `migrations` VALUES (9, '2025_03_29_063415_create_laboratory_requests_table', 1);
INSERT INTO `migrations` VALUES (10, '2025_03_29_063420_create_test_results_table', 1);
INSERT INTO `migrations` VALUES (11, '2025_03_29_063426_create_payments_table', 1);
INSERT INTO `migrations` VALUES (12, '2025_04_11_045608_create_laboratory_request_test_table', 1);
INSERT INTO `migrations` VALUES (13, '2025_04_15_055230_add_item_status_to_laboratory_request_test_table', 1);
INSERT INTO `migrations` VALUES (14, '2025_04_15_055310_add_item_status_to_laboratory_request_test_table', 1);
INSERT INTO `migrations` VALUES (15, '2025_04_22_013934_create_wards_table', 1);
INSERT INTO `migrations` VALUES (16, '2025_04_22_013940_create_rooms_table', 1);
INSERT INTO `migrations` VALUES (17, '2025_04_22_013956_create_patient_types_table', 1);
INSERT INTO `migrations` VALUES (18, '2025_04_22_014014_modify_laboratory_requests_table', 1);
INSERT INTO `migrations` VALUES (19, '2025_04_22_103850_add_phlebotomy_tracking_to_laboratory_request_test_table', 1);
INSERT INTO `migrations` VALUES (20, '2025_04_29_005410_create_test_parameters_table', 1);
INSERT INTO `migrations` VALUES (21, '2025_04_29_035643_create_reference_ranges_table', 1);
INSERT INTO `migrations` VALUES (22, '2025_04_29_041902_add_conversion_factor_to_test_parameters_table', 1);
INSERT INTO `migrations` VALUES (23, '2025_04_29_042143_add_units_to_reference_ranges_table', 1);
INSERT INTO `migrations` VALUES (24, '2025_04_29_052122_create_sample_types_table', 1);
INSERT INTO `migrations` VALUES (25, '2025_04_29_055057_add_rejection_reason_to_laboratory_request_test_table', 1);
INSERT INTO `migrations` VALUES (26, 'YYYY_MM_DD_HHMMSS_add_item_status_to_laboratory_request_test_table', 1);
INSERT INTO `migrations` VALUES (27, '2025_04_29_062234_add_flag_to_test_results_table', 2);
INSERT INTO `migrations` VALUES (28, '2025_04_29_063231_add_unit_to_test_results_table', 3);
INSERT INTO `migrations` VALUES (29, '2025_04_29_063301_add_unit_to_reference_ranges_table', 4);
INSERT INTO `migrations` VALUES (30, '2025_04_29_063603_add_unit_to_test_parameters_table', 5);
INSERT INTO `migrations` VALUES (31, '2025_04_29_085539_create_analyzers_table', 6);
INSERT INTO `migrations` VALUES (32, '2025_04_29_085636_add_analyzer_id_to_reference_ranges_table', 7);
INSERT INTO `migrations` VALUES (33, '2025_04_29_091221_update_analyzers_table', 7);
INSERT INTO `migrations` VALUES (34, '2025_04_29_095454_remove_redundant_columns_from_reference_ranges_table', 8);
INSERT INTO `migrations` VALUES (35, '2025_05_02_060523_create_user_roles_table', 9);
INSERT INTO `migrations` VALUES (36, '2025_05_02_061019_create_test_results_table', 10);
INSERT INTO `migrations` VALUES (37, '2024_05_03_000000_add_remarks_to_laboratory_request_test_table', 11);
INSERT INTO `migrations` VALUES (38, '2024_05_03_000001_add_comment_to_laboratory_requests_table', 11);
INSERT INTO `migrations` VALUES (39, '2024_05_04_000000_move_comment_to_laboratory_request_test', 12);
INSERT INTO `migrations` VALUES (40, '2024_05_04_000001_update_test_results_status_enum', 13);
INSERT INTO `migrations` VALUES (41, '2024_05_06_000000_add_tracking_fields_to_lab_request_test', 14);
INSERT INTO `migrations` VALUES (42, '2024_05_07_000000_update_test_result_controller', 14);
INSERT INTO `migrations` VALUES (43, '2025_05_08_000000_create_doctors_corner_tables', 15);
INSERT INTO `migrations` VALUES (45, '2025_05_19_053831_add_nurse_fields_to_medical_histories_table', 16);

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for patient_types
-- ----------------------------
DROP TABLE IF EXISTS `patient_types`;
CREATE TABLE `patient_types`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `patient_types_name_unique`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of patient_types
-- ----------------------------
INSERT INTO `patient_types` VALUES (1, 'Inpatient', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `patient_types` VALUES (2, 'Outpatient', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `patient_types` VALUES (3, 'Emergency', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `patient_types` VALUES (4, 'Observation', '2025-05-15 09:01:53', '2025-05-15 09:01:53');

-- ----------------------------
-- Table structure for patients
-- ----------------------------
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `gender` enum('Male','Female','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `date_of_birth` date NULL DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `civil_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `patients_email_unique`(`email`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 151 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of patients
-- ----------------------------
INSERT INTO `patients` VALUES (1, 'Ottilie', NULL, 'Balistreri', 'olga54@example.com', '+1-575-779-4228', 'Male', '1975-02-07', '71012 Torp Knolls\nCareyview, VA 37763-0425', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (2, 'Dejon', 'j', 'Auer', 'ogreen@example.net', '1-872-625-2892', 'Male', '2021-06-28', '15121 Orn Land\nEast Zitamouth, NH 83635', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (3, 'Genesis', NULL, 'Wilderman', 'qhuels@example.org', '(469) 961-9890', 'Other', '1964-07-05', '5609 Lesch Estates\nAuroreland, CT 00163', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (4, 'Althea', NULL, 'Predovic', 'reichel.carmine@example.org', '(402) 916-5112', 'Female', '1972-04-01', '65834 Romaine Road Apt. 061\nPaigeshire, NM 40349', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (5, 'Clotilde', 't', 'Conroy', 'don34@example.net', '(847) 878-1971', 'Other', '2023-11-05', '24136 Nora Mount Apt. 146\nNorth Waylon, ME 45498', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (6, 'Antwon', NULL, 'Lehner', 'rbogisich@example.com', '361.520.3989', 'Other', '2020-01-18', '397 Kertzmann Track\nDelbertbury, DE 01587', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (7, 'Tyrese', 'o', 'Bosco', 'deichmann@example.com', '628-995-0863', 'Male', '2003-08-07', '35119 Kohler Tunnel\nEast Sonny, WA 73229', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (8, 'Koby', NULL, 'Haag', 'breitenberg.asia@example.net', '848.523.6043', 'Other', '1978-03-24', '4763 Elenor Walk Suite 254\nVerdamouth, GA 50001-4052', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (9, 'Abdul', NULL, 'Howell', 'krista14@example.org', '805-309-7523', 'Other', '1955-04-20', '16692 Champlin Walks Suite 036\nKochborough, NE 53006', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (10, 'Emerald', 'u', 'Boyle', 'ahmad43@example.com', '+1.312.639.2877', 'Other', '1979-01-05', '65226 Stiedemann Way Suite 349\nWest Bernice, FL 82291-6761', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (11, 'Monroe', 'a', 'Hill', 'athena58@example.com', '+15672043058', 'Female', '1948-04-25', '6577 Juana Route\nLake Javierville, LA 33047-2017', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (12, 'Berneice', NULL, 'Erdman', 'ehartmann@example.net', '248-692-0012', 'Female', '1984-11-08', '74783 Aleen Locks Suite 090\nNorth Liam, NV 65439', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (13, 'Estella', 'z', 'Simonis', 'liliana77@example.net', '+14069662006', 'Other', '2010-04-23', '152 Maryse Motorway\nNorth Jerad, DC 38871', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (14, 'Walter', 'k', 'Fisher', 'nmarvin@example.org', '580-250-6152', 'Female', '2013-05-15', '493 Clyde Stravenue Suite 333\nConsuelofort, DE 18784', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (15, 'Pearline', NULL, 'Heller', 'rkshlerin@example.net', '220.759.3973', 'Female', '1986-07-12', '85969 Gerry Estate\nRosettafort, OH 58250', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (16, 'Antwan', NULL, 'King', 'mccullough.zander@example.org', '1-574-930-8928', 'Male', '1955-10-22', '66980 Devon Circle\nKerlukeside, IA 67164', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (17, 'Idella', NULL, 'Feil', 'vhuel@example.org', '+1.623.664.2902', 'Other', '1966-11-24', '46636 Lindgren Center\nHesselfort, NE 86824', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (18, 'Immanuel', 'd', 'Ziemann', 'qhowell@example.com', '1-320-530-8878', 'Male', '1996-01-11', '5486 Lessie Inlet Apt. 430\nEast Ursulaborough, WA 54732', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (19, 'Orion', NULL, 'Ruecker', 'randall36@example.net', '234.485.4455', 'Male', '1962-12-22', '589 Dibbert Springs Apt. 390\nNew Benedict, WA 78072-0224', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (20, 'Eda', NULL, 'Quigley', 'streutel@example.com', '+1 (763) 326-6458', 'Female', '2016-07-30', '96138 Emelia Union\nSouth Althea, MD 53698', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (21, 'Alexandria', NULL, 'Zboncak', 'hilbert44@example.net', '848.870.8443', 'Female', '2000-02-22', '8265 Augusta Flat Suite 581\nGianniville, AL 61338-6287', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (22, 'Pearl', 'o', 'Vandervort', 'alek.mcclure@example.org', '(323) 542-3475', 'Female', '1950-03-11', '70007 Gerry Way\nWest Weldonmouth, NY 40208', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (23, 'Bennie', 'i', 'Smitham', 'goberbrunner@example.net', '+1-520-881-1367', 'Male', '1956-07-22', '5050 Norene Plains Suite 927\nKarolannchester, RI 06246', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (24, 'Laurel', NULL, 'Schroeder', 'emard.dagmar@example.net', '+17819707470', 'Male', '1970-09-26', '516 Reilly Track Suite 258\nNew Georgiannachester, OH 60229', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (25, 'Elvera', 'a', 'Brown', 'birdie90@example.com', '+1-262-388-6719', 'Female', '2005-06-22', '980 Randi Burgs Suite 764\nNorth Michaelburgh, GA 12775', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (26, 'Jacinthe', 'h', 'Sipes', 'terrell.hahn@example.com', '+1-479-203-0082', 'Other', '2018-05-12', '7149 Winona Ferry\nLake Lacy, ME 59841-1205', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (27, 'Sasha', NULL, 'Conn', 'tpfeffer@example.org', '+1-904-896-7376', 'Other', '1952-04-18', '684 Asha Estate\nGoldnershire, IL 83522-1188', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (28, 'Jasen', 'm', 'Feil', 'hmckenzie@example.net', '+1-650-292-6849', 'Female', '2021-09-16', '89150 Crist Radial\nSouth Kieraview, NC 12528-2525', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (29, 'Hertha', NULL, 'Padberg', 'jovani89@example.org', '541.605.5127', 'Female', '1994-02-17', '5548 Abernathy Station\nPort Mathewland, CO 75640', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (30, 'Brandi', NULL, 'Satterfield', 'rowland50@example.net', '619.525.9617', 'Male', '1956-09-24', '42544 Durgan Ford Apt. 859\nNew Dewittfurt, TX 23866-4156', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (31, 'Janice', 'U', 'Kemmer', 'kaleb60@example.net', '(803) 216-0580', 'Other', '1947-11-05', '303 Kohler Greens Suite 626\nO\'Keefeland, CO 85518-6994', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (32, 'Saige', 'p', 'Kohler', 'lacy.trantow@example.com', '1-251-919-1101', 'Other', '1969-07-06', '15644 Gaylord Glens Suite 524\nLavernafort, VA 46963-7293', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (33, 'Jedediah', NULL, 'Greenholt', 'kkuphal@example.net', '1-815-404-2808', 'Other', '2006-10-30', '6047 Bode Wells\nPagacfort, MO 45930', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (34, 'Destinee', NULL, 'Koss', 'bulah.zemlak@example.org', '+1 (417) 637-8563', 'Female', '1980-10-17', '34558 Ankunding Cove Apt. 035\nLuettgenstad, WY 12574-0534', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (35, 'Keven', NULL, 'Sporer', 'mills.stuart@example.com', '+1-540-802-1198', 'Male', '1958-07-04', '2156 Sophia Route\nJayceestad, CA 57774-1312', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (36, 'Nat', NULL, 'McKenzie', 'mstrosin@example.net', '(651) 814-1562', 'Female', '1991-07-21', '115 Ziemann Terrace\nSvenport, HI 87208', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (37, 'Cary', 'm', 'Trantow', 'kutch.cullen@example.com', '(225) 269-9714', 'Male', '1980-11-16', '650 Windler Mountain\nKreigerborough, ND 02031-2946', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (38, 'Ebba', 'c', 'Moen', 'cole18@example.net', '1-786-354-8774', 'Other', '2013-05-08', '36408 Kling Village\nEast Eldridgetown, MN 53255', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (39, 'Carmen', NULL, 'McCullough', 'ksteuber@example.com', '(507) 775-2674', 'Female', '2007-03-08', '9934 Bayer Garden\nHannahfurt, MD 77905', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (40, 'Makenzie', 'g', 'Haley', 'halvorson.fidel@example.com', '+1-283-354-6707', 'Male', '2009-11-05', '282 Wyatt Passage Apt. 756\nVirgilfurt, HI 02977', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (41, 'Marc', 'd', 'Bernier', 'udaugherty@example.com', '502-314-8581', 'Male', '1977-10-14', '930 Cummings Estates Apt. 809\nPort Rhodaberg, WV 14687-8784', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (42, 'Isaias', 'q', 'King', 'florence.west@example.com', '+1-757-319-3003', 'Other', '1965-03-09', '245 Eugenia Wells Suite 935\nSouth Yasmeenton, ME 94018', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (43, 'Winnifred', NULL, 'Casper', 'nicholas46@example.org', '+1-720-230-4274', 'Male', '1978-06-08', '7538 Westley Coves\nWilmabury, MA 30881-5416', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (44, 'Naomi', NULL, 'Nolan', 'jensen.wisozk@example.net', '1-616-887-9861', 'Male', '1995-09-25', '466 Huel Overpass\nNorth Rachelmouth, FL 77280-5563', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (45, 'Odessa', 'f', 'Leannon', 'mdaniel@example.com', '929-484-2238', 'Female', '1985-08-06', '7403 Hartmann Glen\nDemarcuschester, VA 32058', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (46, 'Cooper', NULL, 'Swift', 'wellington84@example.net', '+19897171893', 'Female', '1963-01-27', '8336 Blick Parks Suite 798\nEast Rusty, AR 63135-3523', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (47, 'Wanda', NULL, 'Altenwerth', 'wanda.oconner@example.net', '559-423-8024', 'Female', '1985-09-13', '30567 Alfreda Light\nHuelsburgh, ND 96307', 'Single', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (48, 'Mohammed', NULL, 'Effertz', 'anika69@example.com', '1-559-277-6977', 'Other', '2019-12-03', '1693 Mosciski Flat Suite 631\nSouth Enid, DE 44965-7220', 'Married', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (49, 'Gertrude', 't', 'Bosco', 'boehm.natalia@example.com', '(386) 783-5105', 'Other', '2013-07-27', '8187 Herzog Flats Suite 672\nEnidshire, ID 87330-3869', 'Widowed', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (50, 'Marcel', NULL, 'Grant', 'rubye47@example.net', '715.935.2740', 'Male', '1954-07-03', '9720 Botsford Fork Suite 530\nHandburgh, NY 80566', 'Divorced', '2025-04-29 06:09:46', '2025-04-29 06:09:46', NULL);
INSERT INTO `patients` VALUES (51, 'Dallin', NULL, 'Ernser', 'karlie.klein@example.org', '458.476.1931', 'Other', '2008-03-02', '60301 Pierce Gateway Apt. 262\nEast Christopher, HI 49977-3776', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (52, 'Francis', 'r', 'Stoltenberg', 'rempel.abdul@example.com', '1-605-853-9373', 'Male', '1963-02-04', '6284 Rosamond Station Suite 882\nBergstromburgh, MI 14688', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (53, 'Coy', NULL, 'Gottlieb', 'gillian.lemke@example.com', '+1-541-415-0721', 'Male', '1956-07-30', '81474 Adams Passage\nDanielport, SD 47073', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (54, 'Dell', 'q', 'Kessler', 'geraldine.braun@example.com', '+1 (786) 216-7998', 'Female', '1960-10-22', '810 Savannah Coves\nHillsmouth, WY 71502', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (55, 'Juana', NULL, 'Runte', 'lukas29@example.org', '1-517-620-5527', 'Other', '1975-10-12', '4245 Marcelle Square\nLake Lillaside, LA 76335', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (56, 'Yoshiko', NULL, 'Ferry', 'blick.anthony@example.com', '+1-445-494-8302', 'Male', '1985-08-06', '997 Kohler Plaza Suite 188\nThielberg, CA 80888-9104', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (57, 'Cade', NULL, 'Littel', 'agustina85@example.com', '+17699868923', 'Male', '1971-02-26', '667 Monroe Harbors Apt. 716\nWalkerfort, CA 83387-6447', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (58, 'Michael', 'i', 'Ankunding', 'cschimmel@example.com', '1-954-399-7738', 'Female', '1956-01-17', '35028 Boyer Estate\nAlichester, NH 78062', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (59, 'Blake', 'j', 'Kub', 'witting.jovani@example.org', '+1-830-238-5640', 'Female', '2014-03-29', '528 Jazmyn Flat\nHillsfort, SC 70933', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (60, 'Libby', 'o', 'Beahan', 'zkunze@example.org', '+1-754-950-5856', 'Male', '1969-05-07', '3539 Lindgren Circles Apt. 561\nEast Sean, NJ 06202-3341', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (61, 'Delmer', 'l', 'Sipes', 'ruecker.tito@example.org', '+1-240-878-8076', 'Male', '1972-01-22', '1340 Roob Inlet Apt. 997\nPort Prudencemouth, MO 26569', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (62, 'Otho', NULL, 'Hilpert', 'gloria.stehr@example.org', '(864) 715-2408', 'Female', '1990-06-11', '270 Jakubowski Walk Suite 206\nNew Roslynland, AZ 07713', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (63, 'Abraham', NULL, 'White', 'qlesch@example.net', '+1-562-556-4245', 'Female', '2023-01-17', '387 Destiney Row\nNorth Lane, WV 18342-8647', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (64, 'Gregorio', NULL, 'Fahey', 'bauch.annetta@example.org', '+16037357846', 'Male', '1960-05-07', '61705 Megane Ford\nWillmsstad, TX 35482', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (65, 'Josh', NULL, 'Carroll', 'pkiehn@example.net', '1-325-790-7121', 'Other', '1998-06-08', '8344 Maynard Prairie Suite 008\nPort Zakary, WA 45184-8680', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (66, 'Kole', NULL, 'Boyer', 'bmante@example.com', '+1-631-634-1549', 'Female', '1995-06-24', '36951 Sadie Parkways\nEast Buddyburgh, OR 45741-4435', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (67, 'Mckayla', 'v', 'Cole', 'kolby.russel@example.org', '682-662-2998', 'Female', '2008-12-27', '93307 Marilyne Field\nAshlymouth, WA 90210', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (68, 'Webster', NULL, 'Hilpert', 'kattie63@example.org', '1-772-327-2940', 'Male', '1963-09-14', '49744 Bennett Corners\nUptonberg, IL 56862', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (69, 'Euna', 'o', 'Bahringer', 'brown25@example.org', '520.951.7461', 'Other', '1984-09-14', '1904 Beatty Drive\nWildermanchester, MA 41755', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (70, 'Merritt', NULL, 'Schmeler', 'angus57@example.com', '1-623-416-7867', 'Female', '1980-12-07', '80953 Sven Pines\nNorth Clifford, MO 10836', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (71, 'Adolfo', NULL, 'Jast', 'huels.brenda@example.com', '1-240-412-9906', 'Male', '1958-06-28', '166 Lang Streets Suite 719\nEast Jamie, GA 83644', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (72, 'Keyon', NULL, 'Rolfson', 'eromaguera@example.com', '1-830-398-3490', 'Male', '1998-06-13', '377 Keven Point Apt. 178\nNorth Domingo, ME 97808-7380', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (73, 'Raphael', NULL, 'Sawayn', 'israel30@example.net', '(209) 765-2729', 'Male', '2002-05-28', '67667 Tremblay Light Apt. 004\nBechtelarchester, TX 32274', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (74, 'Roberta', 'm', 'Goodwin', 'loreilly@example.com', '+1.814.310.0553', 'Other', '2011-05-17', '4585 Damion Crest Suite 256\nClarechester, NC 09400-0085', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (75, 'Madelynn', NULL, 'Skiles', 'ygrimes@example.org', '586.966.9375', 'Female', '1970-07-23', '131 Otho Stream\nSouth Dexter, KS 17963', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (76, 'Kayden', 'd', 'Osinski', 'fwelch@example.net', '518.621.6009', 'Male', '2021-01-31', '7125 Sienna Skyway\nSouth Concepcionburgh, IN 87664', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (77, 'Kaden', 'i', 'Howell', 'qfarrell@example.net', '938.668.6960', 'Female', '2016-06-07', '6983 Douglas Streets\nSouth Stewart, AZ 89073-6957', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (78, 'Sofia', 'e', 'Hayes', 'karen35@example.org', '860.306.7961', 'Male', '2002-10-06', '45888 Quitzon Prairie\nEast Rosalyn, NH 49775', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (79, 'Keara', NULL, 'Friesen', 'emil05@example.net', '+1-785-256-2456', 'Male', '1972-08-29', '245 Legros Vista\nLake Andy, WV 87658-6307', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (80, 'Kade', 'f', 'Swift', 'lorenzo27@example.org', '754-960-9185', 'Male', '1951-11-29', '323 Asa Harbors\nEast Dedrickland, NH 88740-8026', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (81, 'Nico', NULL, 'Dickens', 'anahi80@example.org', '929-419-7247', 'Male', '2009-11-02', '2868 Roberta Station\nWest Dahliahaven, WA 72834-7604', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (82, 'Toni', 's', 'Pfeffer', 'lacy39@example.org', '386.636.1394', 'Female', '1956-08-22', '4945 Madge Valleys\nLake Tyraside, MN 40981-3157', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (83, 'Vida', 'b', 'Beahan', 'qsatterfield@example.org', '508.947.4350', 'Male', '2021-07-31', '822 Wilderman Wall Apt. 224\nNew Jaylon, NV 50610', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (84, 'Cletus', NULL, 'Kuhn', 'kessler.anais@example.org', '+1.843.290.7117', 'Male', '1985-10-06', '743 Deven Groves Suite 115\nLianaton, NM 28410-5129', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (85, 'Jorge', NULL, 'Schneider', 'rdamore@example.org', '+1-743-255-2258', 'Female', '2003-09-27', '308 Chelsea Ways Suite 465\nWest Alainaton, ME 10198-2417', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (86, 'Scot', NULL, 'Stanton', 'damore.laverne@example.net', '1-458-231-7903', 'Male', '1966-05-23', '94295 Annamarie Plaza\nLake Franciscabury, NM 98647', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (87, 'Hailee', NULL, 'Jacobi', 'watson54@example.org', '+1.563.626.2858', 'Male', '1948-11-19', '864 Graham Village\nWest Catharine, DC 47842', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (88, 'Sheila', NULL, 'Daugherty', 'jade98@example.org', '+1.463.491.7408', 'Other', '1969-10-26', '169 Rutherford Lake\nCordeliaborough, DE 44517', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (89, 'Charley', 'w', 'Prosacco', 'kendall.altenwerth@example.com', '1-930-655-4550', 'Female', '2018-01-07', '396 Sporer Road\nNew Jazminstad, MO 39996', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (90, 'Danika', NULL, 'D\'Amore', 'afahey@example.com', '678.792.1173', 'Female', '1957-04-30', '9183 Jolie Place\nSouth Reynoldland, CO 65195', 'Divorced', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (91, 'Hermann', NULL, 'Runte', 'gia.cremin@example.net', '+15125241364', 'Female', '1999-11-07', '90240 Runolfsdottir Islands Apt. 948\nBlandamouth, KY 16959', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (92, 'Leonard', 'f', 'Larkin', 'hyman.monahan@example.org', '743-641-8440', 'Male', '1995-11-25', '690 Callie Garden Suite 792\nSouth Mack, SD 34844', 'Married', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (93, 'Freeda', 'x', 'Dicki', 'qhayes@example.org', '+1-458-486-5912', 'Other', '2022-06-01', '49754 Trevor Flat Apt. 870\nWest Ryleefort, SC 60185', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (94, 'Delmer', 'f', 'Langworth', 'khalvorson@example.net', '(458) 652-6126', 'Male', '1951-06-06', '58735 Braxton Unions\nNew Fermin, AL 28912-0301', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (95, 'Gage', NULL, 'Waters', 'rutherford.darrion@example.net', '(680) 644-3452', 'Female', '1983-12-21', '5542 Ayana Overpass\nYolandaland, NH 02960-0181', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (96, 'Marcelle', 'u', 'Schiller', 'beaulah.grant@example.org', '+1 (810) 548-4668', 'Other', '2013-05-22', '9153 Lynch Vista\nJimmiechester, PA 01465', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (97, 'Genevieve', NULL, 'Miller', 'fernando.grant@example.org', '+13648827445', 'Other', '1997-11-17', '44182 Koch Pine Apt. 867\nIcietown, NM 84876', 'Widowed', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (98, 'Althea', 'w', 'Bechtelar', 'sipes.ressie@example.org', '+1-234-512-2281', 'Other', '1982-10-25', '34906 Bethel Highway\nDickensfort, VA 61388-3706', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (99, 'Kaylie', NULL, 'Hauck', 'aliza52@example.com', '463.724.4799', 'Other', '1955-12-04', '506 Brent Trail\nJerdeberg, WI 19993', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (100, 'Aiden', 'r', 'Hills', 'arno.wiza@example.com', '+19518857342', 'Other', '1959-05-24', '32614 Schroeder Parkways\nMckennaberg, CA 68926', 'Single', '2025-04-29 06:35:46', '2025-04-29 06:35:46', NULL);
INSERT INTO `patients` VALUES (101, 'Vivianne', NULL, 'Padberg', 'dimitri.morar@example.com', '+18473257347', 'Other', '2004-11-28', '66169 Jordi Well Apt. 588\nSouth Jasperborough, MN 48414-4478', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (102, 'Bennett', NULL, 'Kozey', 'gunnar.okuneva@example.com', '(817) 200-7156', 'Male', '1963-09-22', '13306 Easton Court Apt. 724\nYostmouth, NE 50390', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (103, 'Lacey', 'f', 'Connelly', 'vschuster@example.net', '+1-970-554-2139', 'Male', '1972-02-11', '64711 Enola Trail Apt. 803\nNew Dovie, ID 06135', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (104, 'Lenna', NULL, 'Goldner', 'tmertz@example.org', '(520) 306-8716', 'Male', '1990-04-08', '98234 Helena Station\nPort Hudsonfort, WI 69836', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (105, 'Darron', NULL, 'Metz', 'columbus.pfannerstill@example.com', '1-820-626-3128', 'Other', '1971-01-19', '78045 Will Port Suite 816\nAdellamouth, WV 19642-3162', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (106, 'Abbie', 'e', 'Marvin', 'herminio.hills@example.org', '(650) 583-3916', 'Other', '1960-12-16', '566 Mertz Lakes\nPort Rita, TN 66099-2159', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (107, 'Alvis', 'e', 'Kassulke', 'amos17@example.net', '+13617001164', 'Female', '1991-02-25', '798 Kuvalis Motorway\nEast Lempiland, NJ 83557-7095', 'Divorced', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (108, 'Lisandro', 'n', 'Stamm', 'ppfannerstill@example.org', '1-843-489-9023', 'Other', '2019-06-23', '5445 Nolan Cliffs Apt. 603\nJaydenside, NJ 11099-1839', 'Divorced', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (109, 'Linda', 'r', 'Dooley', 'klein.pauline@example.org', '585.618.9990', 'Male', '2012-05-28', '31999 Karina Drive\nNew Reinhold, MA 15128', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (110, 'Lois', 'n', 'Paucek', 'sbarton@example.com', '(585) 849-1885', 'Female', '1993-11-06', '926 Schiller Overpass\nPort Art, ID 59320-3293', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (111, 'Arturo', NULL, 'Mante', 'terrell38@example.com', '346-772-5146', 'Female', '1963-04-11', '762 Bode Crescent\nLemkehaven, TX 21561', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (112, 'Stephen', NULL, 'Howe', 'genoveva59@example.com', '1-347-568-9741', 'Other', '2023-11-24', '824 Watsica Summit Suite 130\nPort Lisa, OH 34449-9763', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (113, 'Joany', 'q', 'Walker', 'eanderson@example.com', '+16694666699', 'Male', '1966-03-18', '29647 Rosenbaum Vista Suite 566\nPort Jessycamouth, MD 42793', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (114, 'Gilberto', NULL, 'Berge', 'elliott34@example.org', '+1-540-664-3190', 'Male', '1966-09-17', '516 Florida Mills Apt. 399\nSterlingchester, NY 96544-2842', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (115, 'Lenna', NULL, 'Fahey', 'ettie.hartmann@example.org', '1-620-862-0348', 'Female', '1998-07-29', '86320 Bartell Oval Suite 724\nMarianebury, SC 31131', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (116, 'Anabel', 'p', 'Hilpert', 'xwatsica@example.net', '1-828-664-5860', 'Other', '1949-07-20', '40077 Davonte Fort\nJaidatown, OK 54571', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (117, 'Brendan', 'f', 'Haag', 'guiseppe73@example.com', '1-415-905-1897', 'Male', '1947-12-11', '377 McDermott Falls\nEast Ronaldostad, WY 38710', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (118, 'Myrtie', 'h', 'Langosh', 'weimann.cindy@example.com', '+19196977591', 'Other', '2011-01-28', '20966 Emmerich Junctions\nEast Bradberg, LA 09013-5750', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (119, 'Brain', NULL, 'Bergstrom', 'jaquan89@example.org', '(253) 719-5576', 'Male', '1956-03-22', '8640 Kuhn Rue Apt. 068\nNorth Hannah, WI 22875-0199', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (120, 'Steve', NULL, 'Sanford', 'terrence.turcotte@example.com', '785.449.2947', 'Other', '1961-10-14', '2195 Schulist Trafficway\nSchimmeltown, GA 82377-3114', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (121, 'Americo', 'w', 'Lemke', 'anais11@example.org', '1-423-845-1161', 'Other', '1986-11-05', '2153 Ransom Mission\nNew Brant, VA 09436', 'Divorced', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (122, 'Burley', 'p', 'Huel', 'angelita.walsh@example.com', '1-985-490-8428', 'Male', '2009-05-21', '313 Nitzsche Lock\nLake Keonville, WI 58588-8759', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (123, 'Jasper', 'b', 'Thompson', 'joyce86@example.net', '(408) 215-4169', 'Female', '1995-08-04', '3007 Marquardt Isle Apt. 213\nCroninbury, PA 58099-4220', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (124, 'Felipa', 'd', 'Nienow', 'cooper98@example.com', '+1-256-768-0776', 'Other', '1982-07-24', '62354 Wiza Estates\nLeolabury, ND 78368-8085', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (125, 'Monica', NULL, 'Spencer', 'nkuphal@example.org', '+18083570642', 'Male', '1961-08-05', '46978 Eva Lake Suite 591\nUptonmouth, IL 68902', 'Divorced', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (126, 'Jaeden', NULL, 'Ratke', 'pearlie52@example.org', '559.596.1912', 'Other', '1984-09-30', '79772 Gavin Club Apt. 279\nWest Kayleighville, VT 41464', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (127, 'Lewis', 'h', 'Jacobs', 'gottlieb.coleman@example.net', '+1 (667) 532-8230', 'Female', '1974-07-19', '756 Nitzsche Squares\nSouth Abeton, ID 00401-2740', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (128, 'Cleveland', NULL, 'Ortiz', 'kirlin.william@example.org', '+1-231-527-9533', 'Male', '1993-02-05', '92819 Carley Mission\nPort Graciemouth, ND 22474-9706', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (129, 'Lincoln', NULL, 'Hickle', 'cara.hyatt@example.org', '912.423.0289', 'Other', '1998-09-06', '501 Kelton Radial Suite 971\nFionaberg, MN 62681', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (130, 'Kurt', NULL, 'Little', 'wuckert.lamont@example.net', '+1 (239) 659-9218', 'Other', '1969-07-26', '32997 Strosin Forest Suite 891\nSporershire, TX 34734', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (131, 'Juana', 'g', 'Tillman', 'marcia.sauer@example.com', '+12284810363', 'Male', '1964-07-19', '598 Claire Crest\nEast Terrance, IA 80321', 'Divorced', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (132, 'Zackary', NULL, 'Nader', 'sierra.adams@example.net', '(917) 744-4642', 'Male', '2007-04-24', '239 Nader Rue\nKuhlmanchester, CA 46190', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (133, 'Aletha', NULL, 'Boyer', 'darlene.huels@example.org', '1-781-382-2996', 'Male', '1959-11-19', '764 Murazik Village Apt. 129\nNew Tad, MI 58032-4888', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (134, 'Ethyl', 'j', 'White', 'gerhold.dangelo@example.com', '+1-479-200-0826', 'Male', '1985-04-11', '5960 Ronaldo Springs\nSouth Lazaro, CT 17613-6218', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (135, 'April', NULL, 'Kris', 'aleen.ryan@example.net', '+1-805-315-1488', 'Female', '1963-08-09', '4144 Toni Valley Apt. 086\nBoscoshire, ND 03225-3796', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (136, 'Rex', NULL, 'Osinski', 'abdul.funk@example.net', '615-545-7771', 'Female', '1986-07-14', '5420 Brannon Cove Suite 685\nPort Colton, PA 22307-4754', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (137, 'Lawrence', 't', 'Heller', 'blanche.cummings@example.org', '1-623-598-7861', 'Female', '2009-12-26', '10219 Caden Landing Suite 887\nAdamshire, SC 07363-0141', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (138, 'Guy', 'r', 'Kulas', 'dschneider@example.org', '+1 (304) 988-9331', 'Male', '1947-12-03', '61164 Grady Rapid\nSporerstad, TX 58502', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (139, 'Kyra', 'y', 'Shields', 'richmond.koch@example.org', '570.499.0094', 'Female', '1946-12-20', '70811 Blanda Underpass Apt. 646\nNew Genesishaven, LA 22814-2847', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (140, 'Omer', NULL, 'Aufderhar', 'aglae.upton@example.org', '+17473653894', 'Other', '2003-06-02', '6042 Edna Burgs\nLarissatown, WI 78349-2821', 'Divorced', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (141, 'Bridgette', NULL, 'Kozey', 'ocie10@example.com', '386.482.9715', 'Female', '1952-03-01', '3567 Elisha Meadows\nPort Kiraview, FL 48619-6813', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (142, 'Floyd', 's', 'Crist', 'moshe.considine@example.org', '559.245.4038', 'Male', '1966-02-17', '443 Arnulfo Fields Suite 695\nAbshireton, LA 72958', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (143, 'Sister', 'z', 'Kiehn', 'qhodkiewicz@example.org', '(810) 310-4320', 'Male', '1998-08-31', '9585 Gleason Isle Apt. 560\nPort Bernardostad, RI 18801', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (144, 'Nedra', NULL, 'Hane', 'darian.borer@example.net', '1-608-308-7812', 'Other', '2013-01-28', '9740 Volkman Curve Suite 653\nLake Tyrellland, IA 58438-0496', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (145, 'Demond', NULL, 'Rolfson', 'issac94@example.org', '(346) 858-3373', 'Male', '1960-09-15', '77976 Ayla Plaza Suite 758\nJacobsonchester, CO 10000', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (146, 'Kelsie', NULL, 'Kuphal', 'easter79@example.com', '+1.251.580.1800', 'Female', '2016-05-22', '936 Taya Stream Apt. 384\nJaskolskiburgh, IN 27908-5128', 'Married', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (147, 'Hellen', NULL, 'Collier', 'haag.river@example.com', '+1-843-938-2406', 'Female', '1983-12-02', '6781 Kshlerin Ports\nRoweshire, CO 03101-7855', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (148, 'Eldred', NULL, 'Friesen', 'rreinger@example.org', '651-546-4594', 'Female', '1979-04-09', '11419 Smitham Valley\nNorth Rory, NC 60404', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (149, 'Margarete', 'o', 'Gislason', 'sarai35@example.org', '+1-361-728-7793', 'Female', '1948-09-21', '976 Schuppe Shoal\nWest Rupert, WV 71986', 'Widowed', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);
INSERT INTO `patients` VALUES (150, 'Pinkie', 'n', 'Strosin', 'angie.reynolds@example.com', '1-628-450-4193', 'Other', '1965-07-27', '1121 Champlin Lakes\nHoseamouth, MO 55677', 'Single', '2025-04-29 06:36:30', '2025-04-29 06:36:30', NULL);

-- ----------------------------
-- Table structure for payments
-- ----------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `laboratory_request_id` bigint(0) UNSIGNED NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `payment_date` timestamp(0) NOT NULL,
  `status` enum('pending','completed','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `payments_laboratory_request_id_foreign`(`laboratory_request_id`) USING BTREE,
  CONSTRAINT `payments_laboratory_request_id_foreign` FOREIGN KEY (`laboratory_request_id`) REFERENCES `laboratory_requests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of payments
-- ----------------------------

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(0) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp(0) NULL DEFAULT NULL,
  `expires_at` timestamp(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------
INSERT INTO `personal_access_tokens` VALUES (1, 'App\\Models\\User', 1, 'api-token', '474f6918ae4109cd269edb3eef93d4eef7efa25fba563b94e9c85717bc910348', '[\"*\"]', '2025-05-23 01:13:17', NULL, '2025-04-29 06:11:20', '2025-05-23 01:13:17');
INSERT INTO `personal_access_tokens` VALUES (2, 'App\\Models\\User', 1, 'api-token', '26b7a9a7074950885eceda806901dc448240434b639673cc2f642a577729af01', '[\"*\"]', '2025-05-15 08:59:13', NULL, '2025-05-15 00:16:55', '2025-05-15 08:59:13');

-- ----------------------------
-- Table structure for physical_exams
-- ----------------------------
DROP TABLE IF EXISTS `physical_exams`;
CREATE TABLE `physical_exams`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `nurse_id` bigint(0) UNSIGNED NOT NULL,
  `vitals` json NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `symptoms` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `physical_exams_consultation_id_foreign`(`consultation_id`) USING BTREE,
  INDEX `physical_exams_nurse_id_foreign`(`nurse_id`) USING BTREE,
  CONSTRAINT `physical_exams_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `physical_exams_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of physical_exams
-- ----------------------------

-- ----------------------------
-- Table structure for prescriptions
-- ----------------------------
DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint(0) UNSIGNED NOT NULL,
  `doctor_id` bigint(0) UNSIGNED NOT NULL,
  `prescription` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `prescriptions_consultation_id_foreign`(`consultation_id`) USING BTREE,
  INDEX `prescriptions_doctor_id_foreign`(`doctor_id`) USING BTREE,
  CONSTRAINT `prescriptions_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of prescriptions
-- ----------------------------

-- ----------------------------
-- Table structure for reference_ranges
-- ----------------------------
DROP TABLE IF EXISTS `reference_ranges`;
CREATE TABLE `reference_ranges`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_parameter_id` bigint(0) UNSIGNED NOT NULL,
  `analyzer_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `si_range` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Display string for SI range, e.g., 4.0-11.0',
  `si_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `conv_range` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Display string for Conventional range',
  `conv_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `low_critical_value` decimal(10, 4) NULL DEFAULT NULL,
  `low_value` decimal(10, 4) NULL DEFAULT NULL,
  `high_value` decimal(10, 4) NULL DEFAULT NULL,
  `high_critical_value` decimal(10, 4) NULL DEFAULT NULL,
  `age_min` int(0) NOT NULL,
  `age_max` int(0) NOT NULL,
  `age_unit` enum('Days','Months','Years') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Any') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Any',
  `created_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ref_range_lookup_index`(`test_parameter_id`, `age_min`, `age_max`, `age_unit`, `gender`) USING BTREE,
  CONSTRAINT `reference_ranges_test_parameter_id_foreign` FOREIGN KEY (`test_parameter_id`) REFERENCES `test_parameters` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 33 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of reference_ranges
-- ----------------------------
INSERT INTO `reference_ranges` VALUES (1, 1, 1, '4 - 11', '10^9/L', '4 - 11', '10^9/L', 2.5000, 4.0000, 11.0000, 15.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (2, 1, 2, '4 - 11', '10^9/L', '4 - 11', '10^9/L', 2.5000, 4.0000, 11.0000, 15.5000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (3, 1, 7, '4.5 - 9.5', '10^9/L', '4.5 - 9.5', '10^9/L', 3.0000, 4.5000, 9.5000, 14.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (4, 2, 1, '4.5 - 5.5', '10^12/L', '4.5 - 5.5', '10^12/L', 3.5000, 4.5000, 5.5000, 6.5000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (5, 2, 2, '4 - 5.3', '10^12/L', '4 - 5.3', '10^12/L', 3.0000, 4.0000, 5.3000, 6.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (6, 2, 7, '4.2 - 5.4', '10^12/L', '4.2 - 5.4', '10^12/L', 3.2000, 4.2000, 5.4000, 6.2000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (7, 3, 1, '12 - 16', 'g/L', '12 - 16', 'g/L', 9.0000, 12.0000, 16.0000, 18.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (8, 3, 2, '12 - 16', 'g/L', '12 - 16', 'g/L', 8.0000, 12.0000, 16.0000, 20.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (9, 3, 7, '12.5 - 16.5', 'g/L', '12.5 - 16.5', 'g/L', 9.5000, 12.5000, 16.5000, 19.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-05-21 08:47:01');
INSERT INTO `reference_ranges` VALUES (10, 4, 1, '37 - 47', 'L/L', '37 - 47', 'L/L', 27.0000, 37.0000, 47.0000, 55.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (11, 4, 2, '38 - 48', 'L/L', '38 - 48', 'L/L', 28.0000, 38.0000, 48.0000, 57.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (12, 4, 7, '36 - 46', 'L/L', '36 - 46', 'L/L', 26.0000, 36.0000, 46.0000, 54.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (13, 5, 1, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (14, 5, 2, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (15, 5, 7, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:49:35', '2025-04-29 09:49:35');
INSERT INTO `reference_ranges` VALUES (16, 1, 1, '4 - 11', '10^9/L', '4 - 11', '10^9/L', 2.5000, 4.0000, 11.0000, 15.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (17, 1, 2, '4 - 11', '10^9/L', '4 - 11', '10^9/L', 2.5000, 4.0000, 11.0000, 15.5000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (18, 1, 7, '4.5 - 9.5', '10^9/L', '4.5 - 9.5', '10^9/L', 3.0000, 4.5000, 9.5000, 14.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (19, 2, 1, '4.5 - 5.5', '10^12/L', '4.5 - 5.5', '10^12/L', 3.5000, 4.5000, 5.5000, 6.5000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (20, 2, 2, '4 - 5.3', '10^12/L', '4 - 5.3', '10^12/L', 3.0000, 4.0000, 5.3000, 6.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (21, 2, 7, '4.2 - 5.4', '10^12/L', '4.2 - 5.4', '10^12/L', 3.2000, 4.2000, 5.4000, 6.2000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (22, 3, 1, '12 - 16', 'g/L', '12 - 16', 'g/L', 9.0000, 12.0000, 16.0000, 18.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (23, 3, 2, '12 - 16', 'g/L', '12 - 16', 'g/L', 8.0000, 12.0000, 16.0000, 20.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (24, 3, 7, '12.5 - 16.5', 'g/L', '12.5 - 16.5', 'g/L', 9.5000, 12.5000, 16.5000, 19.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (25, 4, 1, '37 - 47', 'L/L', '37 - 47', 'L/L', 27.0000, 37.0000, 47.0000, 55.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (26, 4, 2, '38 - 48', 'L/L', '38 - 48', 'L/L', 28.0000, 38.0000, 48.0000, 57.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (27, 4, 7, '36 - 46', 'L/L', '36 - 46', 'L/L', 26.0000, 36.0000, 46.0000, 54.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (28, 5, 1, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (29, 5, 2, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (30, 5, 7, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (31, 6, 3, '150 - 450', '10^9/L', '150 - 450', '10^9/L', 100.0000, 150.0000, 450.0000, 600.0000, 0, 99, 'Years', 'Any', NULL, '2025-04-29 09:57:39', '2025-04-29 09:57:39');
INSERT INTO `reference_ranges` VALUES (32, 5, 1, '150-500', 'x10', '150-500', 'x10', 0.0000, 149.0000, 501.0000, 999.0000, 0, 999, 'Years', 'Any', NULL, '2025-05-21 08:32:46', '2025-05-21 08:32:46');

-- ----------------------------
-- Table structure for rooms
-- ----------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_id` bigint(0) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `rooms_name_unique`(`name`) USING BTREE,
  INDEX `rooms_ward_id_foreign`(`ward_id`) USING BTREE,
  CONSTRAINT `rooms_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of rooms
-- ----------------------------
INSERT INTO `rooms` VALUES (1, 'G101', 1, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (2, 'G102', 1, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (3, 'G103', 1, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (4, 'P201', 2, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (5, 'P202', 2, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (6, 'M301', 3, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (7, 'M302', 3, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (8, 'ICU01', 4, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (9, 'ICU02', 4, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (10, 'OPD-E1', 5, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (11, 'OPD-E2', 5, '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `rooms` VALUES (12, 'NICU 101', 6, '2025-05-21 06:55:33', '2025-05-21 06:55:33');

-- ----------------------------
-- Table structure for sample_types
-- ----------------------------
DROP TABLE IF EXISTS `sample_types`;
CREATE TABLE `sample_types`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sample_types
-- ----------------------------
INSERT INTO `sample_types` VALUES (1, 'Blood', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `sample_types` VALUES (2, 'Urine', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `sample_types` VALUES (3, 'Serum', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `sample_types` VALUES (4, 'Plasma', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `sample_types` VALUES (5, 'Stool', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `sample_types` VALUES (6, 'Tissue', '2025-05-15 09:01:53', '2025-05-15 09:01:53');

-- ----------------------------
-- Table structure for test_categories
-- ----------------------------
DROP TABLE IF EXISTS `test_categories`;
CREATE TABLE `test_categories`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of test_categories
-- ----------------------------
INSERT INTO `test_categories` VALUES (1, 'Clinical Chemistry', 'Eos et rerum ducimus nobis quo culpa labore. ', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `test_categories` VALUES (2, 'Serology/Immunology', 'Omnis sed aut culpa qui.', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `test_categories` VALUES (3, 'Microbiology', 'Omnis facere illum deserunt nam voluptates ut quis.', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `test_categories` VALUES (4, 'Hematology', 'Repellat laborum enim optio dolorum porro.', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `test_categories` VALUES (5, 'Histopathology', 'Quod aut nam eum consequatur eaque.', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `test_categories` VALUES (6, 'Molecular Diagnostics', 'Ut dicta est quia vel eius.', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `test_categories` VALUES (7, 'Clinical Microscopy', 'Dignissimos adipisci dicta repudiandae repellendus.', '2025-05-15 09:01:53', '2025-05-15 09:01:53');

-- ----------------------------
-- Table structure for test_parameters
-- ----------------------------
DROP TABLE IF EXISTS `test_parameters`;
CREATE TABLE `test_parameters`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_id` bigint(0) UNSIGNED NOT NULL,
  `test_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `specimen_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `conversion_factor` decimal(15, 8) NULL DEFAULT NULL COMMENT 'Factor to multiply SI value by to get Conventional value',
  `sequence_no` int(0) NOT NULL DEFAULT 0,
  `created_by` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `test_parameters_test_id_foreign`(`test_id`) USING BTREE,
  CONSTRAINT `test_parameters_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of test_parameters
-- ----------------------------
INSERT INTO `test_parameters` VALUES (1, 'White Blood Cell', 4, 'WBC', 'Whole Blood', '10^9/L', 0.00100000, 1, NULL, '2025-04-29 06:09:46', '2025-04-29 06:36:30');
INSERT INTO `test_parameters` VALUES (2, 'Red Blood Cell', 4, 'RBC', 'Whole Blood', '10^12/L', 1.00000000, 2, NULL, '2025-04-29 06:09:46', '2025-04-29 06:36:30');
INSERT INTO `test_parameters` VALUES (3, 'Hemoglobin', 4, 'HGB', 'Whole Blood', 'g/L', 0.10000000, 3, NULL, '2025-04-29 06:09:46', '2025-04-29 06:36:30');
INSERT INTO `test_parameters` VALUES (4, 'Hematocrit', 4, 'HCT', 'Whole Blood', 'L/L', 100.00000000, 4, NULL, '2025-04-29 06:09:46', '2025-04-29 06:36:30');
INSERT INTO `test_parameters` VALUES (5, 'Platelet Count', 4, 'PLT', 'Whole Blood', '10^9/L', 1.00000000, 5, NULL, '2025-04-29 06:09:46', '2025-04-29 06:36:30');
INSERT INTO `test_parameters` VALUES (6, 'Fasting Blood Sugar', 21, 'FBS', 'SERUM', 'umol', 1.00000000, 1, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (7, 'Cholesterol', 21, 'CHO', 'SERUM', 'umol', 1.00000000, 2, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (8, 'Triglycerides', 21, 'TRI', 'SERUM', 'umol', 1.00000000, 3, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (9, 'LDL', 21, 'LDL', 'SERUM', 'umol', 1.00000000, 4, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (10, 'HDL', 21, 'HDL', 'SERUM', 'umol', 1.00000000, 5, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (11, 'Creatinine', 21, 'CREA', 'SERUM', 'umol', 1.00000000, 6, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (12, 'BUN', 21, 'BUN', 'SERUM', 'umol', 1.00000000, 7, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (13, 'Uric Acid', 21, 'UA', 'SERUM', 'umol', 1.00000000, 8, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (14, 'BUN', 48, 'BUN', 'SERUM', 'umol', 1.00000000, 1, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (15, 'Cholesterol', 49, 'CHO', 'SERUM', 'umol', 1.00000000, 2, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (16, 'Fasting Blood Sugar', 46, 'FBS', 'SERUM', 'umol', 1.00000000, 3, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (17, 'Creatinine', 47, 'FBS', 'SERUM', 'umol', 1.00000000, 4, NULL, NULL, NULL);
INSERT INTO `test_parameters` VALUES (18, 'ESR', 4, 'ESR', 'WHOLE BLOOD', 'x10', 1.00000000, 6, NULL, '2025-05-22 07:31:56', '2025-05-22 07:37:27');
INSERT INTO `test_parameters` VALUES (19, 'MCHC', 4, 'MCHC', 'WHOLE BLOOD', 'mg/dL', 8.50000000, 7, NULL, '2025-05-22 07:38:10', '2025-05-22 07:38:10');

-- ----------------------------
-- Table structure for test_results
-- ----------------------------
DROP TABLE IF EXISTS `test_results`;
CREATE TABLE `test_results`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `laboratory_request_id` bigint(0) UNSIGNED NOT NULL,
  `test_id` bigint(0) UNSIGNED NOT NULL,
  `result_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `result_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `test_parameter_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `analyzer_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `status` enum('pending','saved','completed','validated','released') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `test_results_laboratory_request_id_foreign`(`laboratory_request_id`) USING BTREE,
  INDEX `test_results_test_id_foreign`(`test_id`) USING BTREE,
  INDEX `test_results_test_parameter_id_foreign`(`test_parameter_id`) USING BTREE,
  INDEX `test_results_analyzer_id_foreign`(`analyzer_id`) USING BTREE,
  CONSTRAINT `test_results_analyzer_id_foreign` FOREIGN KEY (`analyzer_id`) REFERENCES `analyzers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `test_results_laboratory_request_id_foreign` FOREIGN KEY (`laboratory_request_id`) REFERENCES `laboratory_requests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `test_results_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `test_results_test_parameter_id_foreign` FOREIGN KEY (`test_parameter_id`) REFERENCES `test_parameters` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 122 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of test_results
-- ----------------------------
INSERT INTO `test_results` VALUES (21, 110, 4, '6', NULL, '10^9/L', '', '2025-05-02 08:05:56', '2025-05-07 02:38:50', 1, 1, 'completed');
INSERT INTO `test_results` VALUES (22, 110, 4, '7', 'H', '10^12/L', '', '2025-05-02 08:05:56', '2025-05-07 02:38:50', 2, 1, 'completed');
INSERT INTO `test_results` VALUES (23, 110, 4, '50', 'H', 'g/L', '', '2025-05-02 08:05:56', '2025-05-07 02:38:50', 3, 1, 'completed');
INSERT INTO `test_results` VALUES (24, 110, 4, '9', 'L', 'L/L', '', '2025-05-02 08:05:56', '2025-05-07 02:38:50', 4, 1, 'completed');
INSERT INTO `test_results` VALUES (34, 110, 4, '950', 'H', '10^9/L', 'Remarks for PLT Results', '2025-05-05 01:15:08', '2025-05-07 02:38:50', 5, 1, 'completed');
INSERT INTO `test_results` VALUES (60, 59, 4, '4', NULL, '10^9/L', '', '2025-05-06 01:51:10', '2025-05-07 08:58:16', 1, 2, 'completed');
INSERT INTO `test_results` VALUES (61, 59, 4, '5', NULL, '10^12/L', '', '2025-05-06 01:51:10', '2025-05-07 08:58:16', 2, 2, 'completed');
INSERT INTO `test_results` VALUES (62, 59, 4, '6', 'L', 'g/L', '', '2025-05-06 01:51:10', '2025-05-07 08:58:16', 3, 2, 'completed');
INSERT INTO `test_results` VALUES (63, 59, 4, '7', 'L', 'L/L', '', '2025-05-06 01:51:10', '2025-05-07 08:58:16', 4, 2, 'completed');
INSERT INTO `test_results` VALUES (64, 59, 4, '150', NULL, '10^9/L', '', '2025-05-06 01:51:50', '2025-05-07 08:58:16', 5, 2, 'completed');
INSERT INTO `test_results` VALUES (69, 6, 4, '5', NULL, '10^9/L', '', '2025-05-06 06:52:10', '2025-05-07 11:58:50', 1, 1, 'validated');
INSERT INTO `test_results` VALUES (85, 6, 4, '23', 'H', '10^12/L', '', '2025-05-07 00:24:18', '2025-05-07 11:58:50', 2, 1, 'validated');
INSERT INTO `test_results` VALUES (86, 6, 4, '344', 'H', 'g/L', '', '2025-05-07 00:24:18', '2025-05-07 11:58:50', 3, 1, 'validated');
INSERT INTO `test_results` VALUES (88, 6, 4, '50', 'h', 'L/L', 'Verified', '2025-05-07 02:30:04', '2025-05-07 11:58:50', 4, 1, 'validated');
INSERT INTO `test_results` VALUES (90, 6, 4, '520', 'h', '10^9/L', 'Run Twice', '2025-05-07 02:50:54', '2025-05-07 11:58:50', 5, 1, 'validated');
INSERT INTO `test_results` VALUES (91, 206, 4, '5', NULL, '10^9/L', '', '2025-05-07 03:26:04', '2025-05-07 09:43:13', 1, 1, 'saved');
INSERT INTO `test_results` VALUES (92, 206, 4, '6', 'h', '10^12/L', '', '2025-05-07 03:26:04', '2025-05-07 09:43:13', 2, 1, 'saved');
INSERT INTO `test_results` VALUES (93, 206, 4, '15', NULL, 'g/L', '', '2025-05-07 03:26:04', '2025-05-07 09:43:13', 3, 1, 'saved');
INSERT INTO `test_results` VALUES (94, 206, 4, '40', NULL, 'L/L', 'Something here', '2025-05-07 03:26:04', '2025-05-07 09:43:13', 4, 1, 'saved');
INSERT INTO `test_results` VALUES (96, 206, 4, '200', NULL, '10^9/L', '', '2025-05-07 07:26:38', '2025-05-07 09:43:13', 5, 1, 'saved');
INSERT INTO `test_results` VALUES (98, 207, 21, '250', NULL, 'umol', '', '2025-05-13 00:27:57', '2025-05-15 07:47:05', 6, 3, 'saved');
INSERT INTO `test_results` VALUES (99, 207, 21, '250', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 7, 3, 'saved');
INSERT INTO `test_results` VALUES (100, 207, 21, '8.5', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 8, 3, 'saved');
INSERT INTO `test_results` VALUES (101, 207, 21, '1.0', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 9, 3, 'saved');
INSERT INTO `test_results` VALUES (102, 207, 21, '10', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 10, 3, 'saved');
INSERT INTO `test_results` VALUES (103, 207, 21, '53', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 11, 3, 'saved');
INSERT INTO `test_results` VALUES (104, 207, 21, '.85', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 12, 3, 'saved');
INSERT INTO `test_results` VALUES (105, 207, 21, '25', NULL, 'umol', '', '2025-05-13 00:30:14', '2025-05-15 07:47:05', 13, 3, 'saved');
INSERT INTO `test_results` VALUES (109, 208, 4, '30', 'H', '10^9/L', '', '2025-05-13 23:10:38', '2025-05-13 23:48:51', 1, NULL, 'saved');
INSERT INTO `test_results` VALUES (114, 209, 46, '208', NULL, 'umol', '', '2025-05-14 00:08:10', '2025-05-14 00:08:10', 16, NULL, 'saved');
INSERT INTO `test_results` VALUES (115, 210, 49, '208', NULL, 'umol', '', '2025-05-14 00:17:32', '2025-05-14 01:10:29', 15, 3, 'saved');
INSERT INTO `test_results` VALUES (116, 208, 21, '205', NULL, 'umol', '', '2025-05-14 01:09:35', '2025-05-14 01:09:35', 6, 3, 'saved');
INSERT INTO `test_results` VALUES (117, 211, 21, '250', NULL, 'umol', '', '2025-05-15 00:39:21', '2025-05-15 00:40:18', 6, 4, 'saved');
INSERT INTO `test_results` VALUES (118, 212, 4, '20', 'H', '10^9/L', '', '2025-05-15 00:49:27', '2025-05-15 00:51:04', 1, 1, 'completed');
INSERT INTO `test_results` VALUES (119, 212, 4, '5', NULL, '10^12/L', '', '2025-05-15 00:50:29', '2025-05-15 00:51:04', 2, 1, 'completed');
INSERT INTO `test_results` VALUES (120, 212, 4, '16', NULL, 'g/L', '', '2025-05-15 00:50:29', '2025-05-15 00:51:04', 3, 1, 'completed');
INSERT INTO `test_results` VALUES (121, 212, 4, '0', 'L', 'L/L', '', '2025-05-15 00:50:29', '2025-05-15 00:51:04', 4, 1, 'completed');
INSERT INTO `test_results` VALUES (122, 212, 4, '400', NULL, '10^9/L', '', '2025-05-15 00:50:29', '2025-05-15 00:51:04', 5, 1, 'completed');

-- ----------------------------
-- Table structure for tests
-- ----------------------------
DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_category_id` bigint(0) UNSIGNED NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `price` decimal(10, 2) NULL DEFAULT NULL,
  `reference_range` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `tests_test_category_id_foreign`(`test_category_id`) USING BTREE,
  CONSTRAINT `tests_test_category_id_foreign` FOREIGN KEY (`test_category_id`) REFERENCES `test_categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 51 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tests
-- ----------------------------
INSERT INTO `tests` VALUES (1, 5, 'Lipid Profile', 'Et recusandae eligendi velit nesciunt.', 450.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (2, 2, 'Gram Stain', 'Omnis dolores.', 250.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (3, 2, 'Urinalysis', 'Dolorem magni adipisci rerum.', 300.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (4, 4, 'Complete Blood Count (CBC)', 'Pariatur autem voluptatem aut.', 450.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (5, 1, 'Thyroid Function Test (TFT)', 'Expedita omnis ducimus.', 550.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (6, 2, 'Culture & Sensitivity', 'Voluptatem quo et.', 800.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (7, 7, 'Urinalysis', 'Quisquam dolorum corporis.', 300.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (8, 3, 'Electrolytes Panel', 'Nam voluptates ad non.', 350.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (9, 1, 'Gram Stain', 'Eaque nesciunt qui fugit.', 250.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (10, 3, 'Gram Stain', 'Molestias deserunt distinctio facilis.', 250.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (11, 3, 'Lipid Profile', 'Quisquam voluptatum odio veritatis.', 450.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (12, 4, 'Liver Function Test (LFT)', 'Iusto impedit in.', 400.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (13, 6, 'Complete Blood Count (CBC)', 'Quasi perferendis provident.', 450.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (14, 7, 'Thyroid Function Test (TFT)', 'Mollitia omnis molestiae.', 550.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (15, 3, 'Electrolytes Panel', 'Consequatur qui ducimus consequatur.', 350.00, NULL, NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `tests` VALUES (16, 1, 'Hemoglobin A1c (HbA1c)', 'Asperiores ut adipisci et.', 600.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (17, 4, 'Hemoglobin A1c (HbA1c)', 'Repellat quas quia necessitatibus.', 600.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (18, 4, 'Complete Blood Count (CBC)', 'Dolorum nulla quasi dolores.', 450.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (19, 3, 'Electrolytes Panel', 'Voluptatem autem velit.', 350.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (20, 2, 'Hemoglobin A1c (HbA1c)', 'Saepe doloremque nisi.', 600.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (21, 1, 'Chem 8', 'Et et quo magni.', 450.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (22, 5, 'Lipid Profile', 'Quasi ut recusandae rerum.', 450.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (23, 7, 'Gram Stain', 'Sed neque quaerat.', 250.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (24, 7, 'Lipid Profile', 'Nihil aut sequi iure.', 450.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (25, 6, 'Complete Blood Count (CBC)', 'Dolorum harum molestiae.', 450.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (26, 4, 'Liver Function Test (LFT)', 'Necessitatibus reprehenderit placeat et.', 400.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (27, 3, 'Urinalysis', 'Voluptates temporibus culpa rerum.', 300.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (28, 1, 'Thyroid Function Test (TFT)', 'Voluptas ut eos.', 550.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (29, 2, 'Liver Function Test (LFT)', 'Quo qui voluptates sint.', 400.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (30, 6, 'Hemoglobin A1c (HbA1c)', 'Repudiandae numquam molestias aut.', 600.00, NULL, NULL, '2025-04-29 06:35:46', '2025-04-29 06:35:46');
INSERT INTO `tests` VALUES (31, 4, 'Blood Glucose (Fasting)', 'Aut voluptatum earum alias.', 350.50, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (32, 1, 'Blood Glucose (Fasting)', 'Et sequi earum aut.', 350.50, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (33, 4, 'Hemoglobin A1c (HbA1c)', 'Error ducimus amet.', 600.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (34, 6, 'Thyroid Function Test (TFT)', 'Quis incidunt.', 550.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (35, 6, 'Liver Function Test (LFT)', 'Laudantium maxime consectetur.', 400.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (36, 4, 'Gram Stain', 'Eos exercitationem corrupti.', 250.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (37, 7, 'Electrolytes Panel', 'Corrupti atque dolores.', 350.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (38, 5, 'Lipid Profile', 'Quo sint.', 450.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (39, 1, 'Blood Glucose (Fasting)', 'Facere quis consequatur eos porro.', 350.50, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (40, 2, 'Electrolytes Panel', 'Non omnis sapiente rerum.', 350.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (41, 7, 'Blood Glucose (Fasting)', 'Est voluptas commodi laborum.', 350.50, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (42, 7, 'Blood Glucose (Fasting)', 'Maxime quis quo.', 350.50, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (43, 3, 'Blood Glucose (Fasting)', 'Sed consectetur molestiae.', 350.50, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (44, 5, 'Liver Function Test (LFT)', 'Ex dolor eum temporibus.', 400.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (45, 6, 'Culture & Sensitivity', 'Ratione iure animi.', 800.00, NULL, NULL, '2025-04-29 06:36:30', '2025-04-29 06:36:30');
INSERT INTO `tests` VALUES (46, 1, 'FBS', NULL, 500.00, NULL, NULL, NULL, NULL);
INSERT INTO `tests` VALUES (47, 1, 'Creatinine', NULL, 300.00, NULL, NULL, NULL, NULL);
INSERT INTO `tests` VALUES (48, 1, 'BUN', NULL, 1500.00, NULL, NULL, NULL, NULL);
INSERT INTO `tests` VALUES (49, 1, 'Cholesterol', NULL, 300.00, NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for user_roles
-- ----------------------------
DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(0) UNSIGNED NOT NULL,
  `role_type` enum('pathologist','verifier','performer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_roles_user_id_role_type_unique`(`user_id`, `role_type`) USING BTREE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_roles
-- ----------------------------
INSERT INTO `user_roles` VALUES (1, 2, 'pathologist', 1, '2025-05-02 06:17:30', '2025-05-02 06:17:30');
INSERT INTO `user_roles` VALUES (2, 3, 'pathologist', 1, '2025-05-02 06:17:30', '2025-05-02 06:17:30');
INSERT INTO `user_roles` VALUES (3, 4, 'pathologist', 1, '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `user_roles` VALUES (4, 5, 'verifier', 1, '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `user_roles` VALUES (5, 6, 'verifier', 1, '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `user_roles` VALUES (6, 7, 'verifier', 1, '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `user_roles` VALUES (7, 8, 'performer', 1, '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `user_roles` VALUES (8, 9, 'performer', 1, '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `user_roles` VALUES (9, 10, 'performer', 1, '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `user_roles` VALUES (10, 11, 'performer', 1, '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `user_roles` VALUES (11, 12, 'verifier', 1, '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `user_roles` VALUES (12, 12, 'performer', 1, '2025-05-02 06:17:32', '2025-05-02 06:17:32');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp(0) NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'Admin User', 'admin@example.com', '2025-04-29 06:09:46', '$2y$12$X3sw/c4oAlItOdDMndEDCur2iOXAGJjlH3Rybt/ju8rOdXaRc24l.', NULL, '2025-04-29 06:09:46', '2025-04-29 06:09:46');
INSERT INTO `users` VALUES (2, 'Dr. Maria Santos', 'maria.santos@lab.com', '2025-05-02 06:17:30', '$2y$12$.5hVrIR3bwTDH4u6NJCRheFh9Kdc.o92IDr2Whk5Le/Wq/Ku1ZRXy', 'tBbqcq62eo', '2025-05-02 06:17:30', '2025-05-02 06:17:30');
INSERT INTO `users` VALUES (3, 'Dr. John Smith', 'john.smith@lab.com', '2025-05-02 06:17:30', '$2y$12$S4LYX9FdOjfG8dIfmurfweaZZZ.5mBxWLHhBPpR3EgtFfuQyqt3FC', 'I8pxW6Z5mW', '2025-05-02 06:17:30', '2025-05-02 06:17:30');
INSERT INTO `users` VALUES (4, 'Dr. Sarah Chen', 'sarah.chen@lab.com', '2025-05-02 06:17:31', '$2y$12$NgiA1OBFkHOrs/KKcHIi5OQ6DR/yiUlkMdAXn/weGwhoykM22MmEm', '9vm9JkEvYn', '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `users` VALUES (5, 'Emma Wilson', 'emma.wilson@lab.com', '2025-05-02 06:17:31', '$2y$12$Emhuc9A46ZyDroFHmR91Iu//dbh4Ac.MSAoZ5oy81Zhtp99j9YMhq', 'd6MkyMQJue', '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `users` VALUES (6, 'Michael Lee', 'michael.lee@lab.com', '2025-05-02 06:17:31', '$2y$12$0o1E/4jg7cbcMWngkC5BfeA7T0bLhDT6Q/oY8xwNLg7FxtCQm1/ge', 'SMGdpVKnqp', '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `users` VALUES (7, 'David Garcia', 'david.garcia@lab.com', '2025-05-02 06:17:31', '$2y$12$aw3gqL4M6JkshDfFhN/ZieyhUFA7xOrs2qaO0DOkGzNz2VQ2THMca', 'VkddprXc8J', '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `users` VALUES (8, 'Lisa Kim', 'lisa.kim@lab.com', '2025-05-02 06:17:31', '$2y$12$zGhczVWF1fRXqW7MHmTXGuY6IHfE/tMGze1NZf6eg9zqyTJRpA4DO', 'MTOPaevBRd', '2025-05-02 06:17:31', '2025-05-02 06:17:31');
INSERT INTO `users` VALUES (9, 'James Rodriguez', 'james.rodriguez@lab.com', '2025-05-02 06:17:32', '$2y$12$ewe7.XP2Mny2sZiwthb48.Z1GAaDiYWTveOPXo5/IKTfb1Q/vL1te', 'mHG36qKRJI', '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `users` VALUES (10, 'Michelle Brown', 'michelle.brown@lab.com', '2025-05-02 06:17:32', '$2y$12$y1lMRuXDiuCBMxCbk2g/3O/LHfiPZuxEpJqs/RZcwMpFAqda0JU82', 'yHnoazazKR', '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `users` VALUES (11, 'Robert Johnson', 'robert.johnson@lab.com', '2025-05-02 06:17:32', '$2y$12$u4eQWfhbSxfqwfL.pyy40.PnQ2a8.p5UvXGectDeO/ih..TI4p67C', 'YZqoMxr2Eb', '2025-05-02 06:17:32', '2025-05-02 06:17:32');
INSERT INTO `users` VALUES (12, 'Alex Taylor', 'alex.taylor@lab.com', '2025-05-02 06:17:32', '$2y$12$tNBCbjDXyBHLta0RM2YEf.C6AAOE5uFzjvS1.5nf8rQzg2b81HFYa', 'tKVjkLDFbz', '2025-05-02 06:17:32', '2025-05-02 06:17:32');

-- ----------------------------
-- Table structure for wards
-- ----------------------------
DROP TABLE IF EXISTS `wards`;
CREATE TABLE `wards`  (
  `id` bigint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `wards_name_unique`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of wards
-- ----------------------------
INSERT INTO `wards` VALUES (1, 'General Ward', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `wards` VALUES (2, 'Pediatric Ward', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `wards` VALUES (3, 'Maternity Ward', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `wards` VALUES (4, 'ICU', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `wards` VALUES (5, 'OPD', '2025-05-15 09:01:53', '2025-05-15 09:01:53');
INSERT INTO `wards` VALUES (6, 'NICU', '2025-05-21 06:54:56', '2025-05-21 06:55:17');

SET FOREIGN_KEY_CHECKS = 1;
