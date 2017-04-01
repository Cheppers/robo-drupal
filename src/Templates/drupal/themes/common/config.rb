
require 'minigit'

is_production = (environment == :production)

css_dir = 'css'
sass_dir = 'css'
images_dir = 'images'
generated_images_dir = images_dir + '/generated'
javascripts_dir = 'js'
fonts_dir = 'fonts'
relative_assets = true
output_style = is_production ? :compressed : :expanded
line_comments = !is_production
sourcemap = !is_production
debug_info = !is_production

if (is_production)
  asset_cache_buster do |path, file|
    options = {:query => nil}

    if file
      file_commit_hash = MiniGit::Capturing
        .log({:n => 1}, "--format='%h'", '--', file.path)
        .tr("\n", '')

      options[:query] = file_commit_hash.blank ? file.mtime.strftime('%s') : file_commit_hash
    end

    return options
  end
end
