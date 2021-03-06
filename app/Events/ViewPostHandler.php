<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Carbon\Carbon;
use App\Entities\Post;
use App\Entities\ViewCount;

class ViewPostHandler
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Hàm xử lí khi event được nhắc đến
     *
     * @param $id id của bài viết
     * @return void
     */
    public function handle($id)
    {
        $post_id = $id;
        $session_name = request()->session_name;

        $isExists = ViewCount::where(compact('post_id', 'session_name'))->first();

        // nếu tồn tại thì kiểm tra xem đã quá thời hạn của ss chưa , nếu quá thì xóa ss và tăng viewer, còn không thì không tăng
        if(!empty($isExists)) {
            if (Carbon::parse($isExists->created_at)->addMinutes(1440)->isPast()) {
                $isExists->delete();
                $isExists = '';
            }
        }

        // khi event được nhắc đến thì ta kiểm tra view_count xem đã tồn tại sesion_name ,
        if (empty($isExists)) {
            ViewCount::create(compact('post_id', 'session_name'));
            $post = Post::find($id);
            $post->increment('count_view');
        }
    }
}
