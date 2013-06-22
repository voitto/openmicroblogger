
# zygote

  true mvc web framework

## Installation

Requirements:

 -- Node.js
 
 -- Postgresql
 
 -- Coffeescript

Installation Commands:

```

$ git clone git@github.com:voitto/zygote.git

$ cd zygote

[[[ edit the server.coffee file to add your own postgresql settings ]]]

$ coffee server.coffee

[[[ browse to http://localhost:4444 ]]]



```

## Example

```coffeescript


app = require( 'zygote' ).config
  port: 4444,
  dbname: 'javascriptly',
  dbuser: 'brian',
  dbpass: '',
  dbhost: 'localhost',
  dbport: 5432

class Post extends app.Model

class Home extends app.View
  constructor: ->
    super
    @controller = new Posts @model, @

class Posts extends app.Controller
  constructor: ( Post ) ->
    super
    Post.find()
  render: ->
    @view.render()

app.get '/', ( req, res ) ->
  @model = new Post
  @view = new Home @model, req, res

app.post '/post/new', ( req, res ) ->
  @fullBody = '';
  req.on 'data', (chunk) =>
    @fullBody += chunk.toString()
  req.on 'end', =>
    @data = JSON.parse @fullBody
    @model = new Post
    @post = @model.create @data
    res.end 'ok'

$('#post-save').click =>
  @model = new Post
  @model.save()
  $( '#post-title' ).val ''
  $( '.modal' ).removeClass 'active'
  $( '.modal-bg' ).remove()


```

## License 

(The MIT License)

Copyright (c) 2013 Brian Hendrickson &lt;bh@bh.ly&gt;

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

