<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/learning-materials/{material}/preview', function (LearningMaterial $material) {
    $processor = app(ContentProcessor::class);
    $compiledContent = $processor->compile($material);
    $stats = $processor->analyzeContent($material);
    
    return view('learning-materials.preview', compact('material', 'compiledContent', 'stats'));
})->name('learning-materials.preview');