-- tbl_file.file_path·full_path 를 서버 절대 경로(/var/www/html/public/uploads/…)에서 저장소 키(public/uploads/…)로 바꾼다.
-- 코드(Storage::keyFromUri)는 절대 경로도 읽으므로 이 마이그레이션을 실행하지 않아도 동작한다.
-- 실행하면 API 응답에 서버 경로가 나가지 않는다.
--
-- 대상: 경로에 /public/uploads/ 가 있는 로컬 행만. s3:// 행은 그대로 둔다.
-- 업로드 폴더가 public/uploads 가 아닌 프로젝트는 아래 '/public/uploads/' 를 그 경로로 바꾼다.
-- 실행 전: 확인 쿼리로 바뀔 행과 결과를 본다. 다시 실행해도 이미 바뀐 행은 건드리지 않는다(앞에 / 가 없어 LOCATE 가 0).

-- 확인: 바뀔 행과 바뀐 뒤 값
SELECT file_id, full_path,
       SUBSTRING(full_path, LOCATE('/public/uploads/', full_path) + 1) AS new_full_path,
       file_path,
       SUBSTRING(file_path, LOCATE('/public/uploads/', file_path) + 1) AS new_file_path
FROM tbl_file
WHERE full_path NOT LIKE 's3://%' AND LOCATE('/public/uploads/', full_path) > 0;

UPDATE tbl_file
SET full_path = SUBSTRING(full_path, LOCATE('/public/uploads/', full_path) + 1)
WHERE full_path NOT LIKE 's3://%' AND LOCATE('/public/uploads/', full_path) > 0;

UPDATE tbl_file
SET file_path = SUBSTRING(file_path, LOCATE('/public/uploads/', file_path) + 1)
WHERE file_path NOT LIKE 's3://%' AND LOCATE('/public/uploads/', file_path) > 0;

-- 확인: 남은 절대 경로 (업로드 폴더가 다른 행 등)
SELECT file_id, file_path, full_path FROM tbl_file WHERE full_path LIKE '/%' OR file_path LIKE '/%';
