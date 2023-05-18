import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    toggle(event) {
        const followUrl = event.target.dataset.followUrlValue;
        const isFollowing = event.target.dataset.following;


        if (isFollowing === '1') {
            this._unfollow(followUrl)
                .then(response => {
                    if (response.status !== 200) {
                        throw new Error('Something went wrong: ' + response.statusText);
                    }
                })
                .then(() => {
                    // update isFollowing attribute
                    event.target.setAttribute('data-following', '0');
                    // update text content
                    event.target.textContent = 'Follow';
                })
        } else {
            this._follow(followUrl)
                .then(response => {
                    if (response.status !== 200) {
                        throw new Error('Something went wrong: ' + response.statusText);
                    }
                })
                .then(() => {
                    // update isFollowing attribute
                    event.target.setAttribute('data-following', '1');
                    // update text content
                    event.target.textContent = 'Unfollow';
                })
        }
    }

    _follow(followUrl) {
        return fetch(followUrl, {
            method: 'POST', headers: {
                'X-Requested-With': 'XMLHttpRequest', // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
    }

    _unfollow(followUrl) {
        return fetch(followUrl, {
            method: 'DELETE', headers: {
                'X-Requested-With': 'XMLHttpRequest', // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
    }
}