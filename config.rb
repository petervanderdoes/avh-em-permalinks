# Require any additional compass plugins here.
project_type = :stand_alone

# Set this to the root of your project when deployed:
http_path = "/"
css_dir = "css/"
sass_dir = "sass/"
images_dir = "images/"
javascripts_dir = "js/"

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
output_style = (environment == :production) ? :compressed : :expanded
# To enable relative paths to assets via compass helper functions. Uncomment:
relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
line_comments = true
environment = :development

sass_options = {:cache_location => "/d1/sass-cache"}
