<?php
// upload folder 만들기
if ( ! function_exists('make_directory'))
{
    function make_directory($path, $mode = 0755): bool
    {
        $path_list = explode(DIRECTORY_SEPARATOR, $path);
        $total_path = FCPATH;
        if(substr($total_path,-1,1) === DIRECTORY_SEPARATOR) $total_path = substr($total_path, 0, strlen($total_path)-1);
        $result = true;
        for($i = 0; $i < count($path_list); $i++) {
            if(!$path_list[$i]) continue;

            $total_path .= ($total_path?'/':'').$path_list[$i];
            if(is_dir($total_path)) {
                continue;
            }else{
                $result = @mkdir($total_path, $mode);
                @chmod($total_path, $mode);
                if(!$result) continue;
            }
        }
        return $result;
    }
}

if( ! function_exists('copy_directory')) {
    function copy_directory($source, $destination, $permissions = 0755): bool
    {
        // 원본 폴더 존재 여부 확인
        if (!is_dir($source)) {
            return false;
        }

        // 대상 폴더가 없으면 생성
        if (!is_dir($destination)) {
            mkdir($destination, $permissions, true);
        }

        // 폴더 열기
        $dir = opendir($source);

        while (($file = readdir($dir)) !== false) {
            // 현재 폴더(.)와 상위 폴더(..) 제외
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $file;

            // 폴더면 재귀 복사
            if (is_dir($sourcePath)) {
                copy_directory($sourcePath, $destinationPath);
            } else {
                // 파일이면 복사
                copy($sourcePath, $destinationPath);
            }
        }

        closedir($dir);

        return true;
    }
}

if( ! function_exists('delete_directory')) {
    function delete_directory($directory): bool
    {
        $dir = rtrim($directory, '/\\');

        if (!is_dir($dir)) {
            return false;
        }

        // 내부 파일 + 하위 폴더 삭제
        delete_files($dir, true);

        // 최상위 폴더 삭제
        return rmdir($dir);
    }
}
