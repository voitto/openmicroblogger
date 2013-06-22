
# Zygote Example
# June 22, 2013
# npm install socket.io pg mustache jquery zygote


app = require( 'zygote' ).config
  port: 4444,
  dbname: 'omb2',
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

