<?php

use App\Http\Controllers\Admin\DiscussionController as AdminDiscussionController;
use App\Http\Controllers\Admin\Discussion\CategoryController as AdminDiscussionCategoryController;
use App\Http\Controllers\Admin\Discussion\SettingController as AdminDiscussionSettingController;

// Discussions
Route::delete('/discussions', [AdminDiscussionController::class, 'massDestroy'])->name('admin.discussions.massDestroy');
Route::get('/discussions/cancel/{discussion?}', [AdminDiscussionController::class, 'cancel'])->name('admin.discussions.cancel');
Route::put('/discussions/publish', [AdminDiscussionController::class, 'massPublish'])->name('admin.discussions.massPublish');
Route::put('/discussions/unpublish', [AdminDiscussionController::class, 'massUnpublish'])->name('admin.discussions.massUnpublish');
Route::get('/discussions/{discussion}/edit', [AdminDiscussionController::class, 'edit'])->name('admin.discussions.edit');
Route::delete('/discussions/{discussion}/delete-image', [AdminDiscussionController::class, 'deleteImage'])->name('admin.discussions.deleteImage');
Route::resource('discussions', AdminDiscussionController::class, ['as' => 'admin'])->except(['show', 'edit']);
// Categories
Route::delete('/discussion/categories', [AdminDiscussionCategoryController::class, 'massDestroy'])->name('admin.discussion.categories.massDestroy');
Route::get('/discussion/categories/cancel/{category?}', [AdminDiscussionCategoryController::class, 'cancel'])->name('admin.discussion.categories.cancel');
Route::put('/discussion/categories/checkin', [AdminDiscussionCategoryController::class, 'massCheckIn'])->name('admin.discussion.categories.massCheckIn');
Route::put('/discussion/categories/publish', [AdminDiscussionCategoryController::class, 'massPublish'])->name('admin.discussion.categories.massPublish');
Route::put('/discussion/categories/unpublish', [AdminDiscussionCategoryController::class, 'massUnpublish'])->name('admin.discussion.categories.massUnpublish');
Route::get('/discussion/categories/{category}/up', [AdminDiscussionCategoryController::class, 'up'])->name('admin.discussion.categories.up');
Route::get('/discussion/categories/{category}/down', [AdminDiscussionCategoryController::class, 'down'])->name('admin.discussion.categories.down');
Route::get('/discussion/categories/{category}/edit', [AdminDiscussionCategoryController::class, 'edit'])->name('admin.discussion.categories.edit');
Route::delete('/discussion/categories/{category}/delete-image', [AdminDiscussionCategoryController::class, 'deleteImage'])->name('admin.discussion.categories.deleteImage');
Route::resource('discussion/categories', AdminDiscussionCategoryController::class, ['as' => 'admin.discussion'])->except(['show', 'edit']);

