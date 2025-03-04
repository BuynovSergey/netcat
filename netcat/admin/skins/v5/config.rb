require 'zlib'

Encoding.default_external = "utf-8"

# Require any additional compass plugins here.

# Set this to the root of your project when deployed:
http_path       = "/netcat/admin/skins/v5/"
css_dir         = "css"
sass_dir        = "sass"
fonts_dir       = "font"
images_dir      = "img"
javascripts_dir = "js"

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
output_style = :compact

# To enable relative paths to assets via compass helper functions. Uncomment:
relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
line_comments = false


# If you prefer the indented syntax, you might want to regenerate this
# project again passing --syntax sass, or you can uncomment this:
# preferred_syntax = :scss
# and then run:
# sass-convert -R --from scss --to sass sass scss && rm -rf sass && mv scss sass


# Make a copy of sprites with a name that has no uniqueness of the hash.
on_sprite_saved do |filename|
  if File.exists?(filename)
    FileUtils.mv filename, filename.gsub(%r{-s[a-z0-9]{10}\.png$}, '.png')
  end
end

# Alter image file names and cache busters in CSS
on_stylesheet_saved do |filename|
  if File.exists?(filename)
    css = File.read filename

    # Replace in stylesheets generated references to sprites
    # by their counterparts without the hash uniqueness.
    css = css.gsub(%r!-s[a-z0-9]{10}\.png!, '.png')

    # Add cache buster to images based on file contents crc32
    css = css.gsub(%r!../(img/[^'"?]+)(\?[^'"]+)?!) do |css_path|
      "../" + $1 + (File.exists?($1) ? "?" + Zlib.crc32(File.open($1, "rb").read).to_s(36) : "")
    end

    File.write(filename, css)
  end
end