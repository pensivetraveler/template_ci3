-- tbl_file.file_link 를 전체 주소(http://호스트:포트/public/uploads/…)에서 저장소 키(public/uploads/…)로 바꾼다.
-- 코드(Model_File → get_file_url)는 읽을 때 키를 지금 서버 기준 주소로 바꾸고, 전체 주소는 그대로 쓰므로
-- 이 마이그레이션을 실행하지 않아도 동작한다. 실행하면 도메인·포트·하위 경로가 바뀌어도 예전 파일 주소가 깨지지 않는다.
--
-- 대상: 로컬 저장 파일(주소에 /public/uploads/ 가 있는 행)만. S3 CDN 주소는 그대로 둔다(CDN 도메인이 바뀌지 않는 한 문제없음).
-- 업로드 폴더가 public/uploads 가 아닌 프로젝트는 아래 '/public/uploads/' 를 그 경로로 바꾼다.
-- 실행 전: 확인 쿼리로 바뀔 행과 결과를 본다. 다시 실행해도 이미 바뀐 행(http 로 시작하지 않음)은 건드리지 않는다.

-- 확인: 바뀔 행과 바뀐 뒤 값
SELECT file_id, file_link,
       SUBSTRING(file_link, LOCATE('/public/uploads/', file_link) + 1) AS new_file_link
FROM tbl_file
WHERE file_link REGEXP '^(https?:)?//' AND LOCATE('/public/uploads/', file_link) > 0;

UPDATE tbl_file
SET file_link = SUBSTRING(file_link, LOCATE('/public/uploads/', file_link) + 1)
WHERE file_link REGEXP '^(https?:)?//' AND LOCATE('/public/uploads/', file_link) > 0;

-- 확인: 남은 전체 주소(S3 CDN 등)
SELECT file_id, file_link FROM tbl_file WHERE file_link REGEXP '^(https?:)?//';
