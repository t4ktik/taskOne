<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class AuthorizeChatAttachment
{
    /**
     * Chatify 1.0 serves attachments by file name. Only a participant of the
     * message that stored that name may download it, and the path must stay
     * inside the attachments directory.
     */
    public function handle($request, Closure $next)
    {
        $fileName = $request->route('fileName');

        if(!is_string($fileName) || $fileName === '' || basename($fileName) !== $fileName || strpbrk($fileName, "/\\%") !== false)
        {
            abort(404);
        }

        $folder = storage_path('app/public/' . trim((string) config('chatify.attachments.folder'), '/'));
        $path   = $folder . DIRECTORY_SEPARATOR . $fileName;

        $realFolder = realpath($folder);
        $realPath   = realpath($path);

        if($realFolder === false || $realPath === false || !str_starts_with($realPath, $realFolder . DIRECTORY_SEPARATOR))
        {
            abort(404);
        }

        $user = $request->user();

        if(!$user)
        {
            return redirect()->guest(route('login'));
        }

        $escaped = addcslashes($fileName, '\\%_');
        $allowed = DB::table('messages')
            ->where(function ($query) use ($user){
                $query->where('from_id', $user->id)->orWhere('to_id', $user->id);
            })
            ->where(function ($query) use ($fileName, $escaped){
                $query->where('attachment', $fileName)
                    ->orWhere('attachment', 'like', $escaped . ',%');
            })
            ->exists();

        if(!$allowed)
        {
            abort(404);
        }

        return $next($request);
    }
}
