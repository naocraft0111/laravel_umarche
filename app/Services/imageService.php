<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use InterventionImage;

class ImageService
{
    public static function upload($imageFile, $folderName){
        // dd($imageFile['image']);
        // 画像ファイルが配列かどうか判定（配列のままだと、InterventionImageが動かない）
        if(is_array($imageFile)) {
            $file = $imageFile['image'];
        } else {
            $file = $imageFile;
        }

        // ファイル名を重複させないためにランダムな名前をつけるためにuniqid()を使う
        $fileName = uniqid(rand().'_');
        // 拡張子をつける
        $extension = $file->extension();
        // ファイル名と拡張子を合体させる
        $fileNameToStore = $fileName. '.' . $extension;
        // 画像をリサイズする
        $resizedImage = InterventionImage::make($file)
            ->resize(1920, 1080)->encode();
        // publicフォルダでfolderNameに指定した場所にfileNameToStoreと共にリサイズした画像を入れる
        Storage::put('public/' . $folderName . '/' . $fileNameToStore, $resizedImage);

        return $fileNameToStore;
    }
}
