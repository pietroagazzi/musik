import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    toggle(event) {
        // get the action url
        const followUrl = event.target.dataset.followUrlValue;

        // get the isFollowing attribute (0 if not following, 1 if following)
        const isFollowing = event.target.dataset.followingValue;

        // get the csrf token
        const csrfToken = event.target.dataset.csrfValue;

        if (isFollowing === '1') {
            this._unfollow(followUrl, csrfToken)
                .then(response => {
                    if (response.status !== 200) {
                        throw new Error('Something went wrong: ' + response.statusText);
                    }

                    return response.json();
                })
                .then(data => {
                    let token = data.csrf_token;

                    event.target.setAttribute('data-csrf-value', token);

                    // update isFollowing attribute
                    event.target.setAttribute('data-following-value', '0');
                    // update text content
                    event.target.textContent = 'Follow';
                });
        } else {
            this._follow(followUrl, csrfToken)
                .then(response => {
                    if (response.status !== 200) {
                        throw new Error('Something went wrong: ' + response.statusText);
                    }

                    return response.json();
                })
                .then(data => {
                    let token = data.csrf_token;

                    event.target.setAttribute('data-csrf-value', token);
                    
                    // update isFollowing attribute
                    event.target.setAttribute('data-following-value', '1');
                    // update text content
                    event.target.textContent = 'Unfollow';
                });
        }
    }

    _follow(followUrl, csrfToken) {
        return fetch(followUrl, {
            method: 'POST', headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': csrfToken
            }
        })
    }

    _unfollow(followUrl, csrfToken) {
        return fetch(followUrl, {
            method: 'DELETE', headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': csrfToken
            }
        })
    }
}