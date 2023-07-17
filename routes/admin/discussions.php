<?php

use App\Http\Controllers\Admin\DiscussionController as AdminDiscussionController;
use App\Http\Controllers\Admin\Discussion\CategoryController as AdminDiscussionCategoryController;
use App\Http\Controllers\Admin\Discussion\SettingController as AdminDiscussionSettingController;

// Categories
Route::delete('/discussions/categories', [AdminDiscussionCategoryController::class, 'massDestroy'])->name('admin.discussions.categories.massDestroy');
Route::get('/discussions/categories/cancel/{category?}', [AdminDiscussionCategoryController::class, 'cancel'])->name('admin.discussions.categories.cancel');
Route::put('/discussions/categories/checkin', [AdminDiscussionCategoryController::class, 'massCheckIn'])->name('admin.discussions.categories.massCheckIn');
Route::put('/discussions/categories/publish', [AdminDiscussionCategoryController::class, 'massPublish'])->name('admin.discussions.categories.massPublish');
Route::put('/discussions/categories/unpublish', [AdminDiscussionCategoryController::class, 'massUnpublish'])->name('admin.discussions.categories.massUnpublish');
Route::get('/discussions/categories/{category}/up', [AdminDiscussionCategoryController::class, 'up'])->name('admin.discussions.categories.up');
Route::get('/discussions/categories/{category}/down', [AdminDiscussionCategoryController::class, 'down'])->name('admin.discussions.categories.down');
Route::get('/discussions/categories/{category}/edit', [AdminDiscussionCategoryController::class, 'edit'])->name('admin.discussions.categories.edit');
Route::delete('/discussions/categories/{category}/delete-image', [AdminDiscussionCategoryController::class, 'deleteImage'])->name('admin.discussions.categories.deleteImage');
Route::resource('discussions/categories', AdminDiscussionCategoryController::class, ['as' => 'admin.discussions'])->except(['show', 'edit']);
// Settings
Route::get('/discussions/settings', [AdminDiscussionSettingController::class, 'index'])->name('admin.discussions.settings.index');
Route::patch('/discussions/settings', [AdminDiscussionSettingController::class, 'update'])->name('admin.discussions.settings.update');
// Discussions
Route::delete('/discussions', [AdminDiscussionController::class, 'massDestroy'])->name('admin.discussions.massDestroy');
Route::get('/discussions/cancel/{discussion?}', [AdminDiscussionController::class, 'cancel'])->name('admin.discussions.cancel');
Route::put('/discussions/publish', [AdminDiscussionController::class, 'massPublish'])->name('admin.discussions.massPublish');
Route::put('/discussions/unpublish', [AdminDiscussionController::class, 'massUnpublish'])->name('admin.discussions.massUnpublish');
Route::put('/discussions/checkin', [AdminDiscussionController::class, 'massCheckIn'])->name('admin.discussions.massCheckIn');
Route::get('/discussions/{discussion}/edit', [AdminDiscussionController::class, 'edit'])->name('admin.discussions.edit');
Route::delete('/discussions/{discussion}/delete-image', [AdminDiscussionController::class, 'deleteImage'])->name('admin.discussions.deleteImage');
Route::resource('discussions', AdminDiscussionController::class, ['as' => 'admin'])->except(['show', 'edit']);
